<?php
// actions/generate_settlement.php
// Supplier EOD Settlement & Loan Deduction Controller (Mudiyala End-of-Day Sheet)

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=settlement_calculate');
    exit;
}

$consignmentId        = intval($_POST['consignment_id'] ?? 0);
$commissionRate       = floatval($_POST['commission_rate'] ?? DEFAULT_COMMISSION_RATE);
$freightDeduction     = floatval($_POST['freight_deduction'] ?? 0.00);
$helperBattaDeduction = floatval($_POST['helper_batta_deduction'] ?? 0.00);
$coolieDeduction      = floatval($_POST['coolie_deduction'] ?? 0.00);
$loanDeduction        = floatval($_POST['loan_deduction'] ?? 0.00);

if ($consignmentId <= 0) {
    setFlash('danger', 'Consignment ID is required.');
    header('Location: ../index.php?page=settlement_calculate');
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();

    // Fetch consignment and supplier
    $stmtC = $db->prepare("
        SELECT c.*, s.id AS supplier_id, s.name AS supplier_name, s.current_loan_balance 
        FROM consignments c
        JOIN suppliers s ON c.supplier_id = s.id
        WHERE c.id = :cid FOR UPDATE
    ");
    $stmtC->execute([':cid' => $consignmentId]);
    $consignment = $stmtC->fetch();

    if (!$consignment) {
        throw new Exception("Consignment not found.");
    }

    if ($consignment['status'] === 'settled') {
        throw new Exception("This consignment has already been settled.");
    }

    // Compute gross revenue for sold fish items
    $stmtSales = $db->prepare("
        SELECT SUM(ii.line_total) AS total_revenue
        FROM fish_items f
        JOIN invoice_items ii ON f.id = ii.fish_item_id
        WHERE f.consignment_id = :cid AND f.status IN ('sold', 'settled')
    ");
    $stmtSales->execute([':cid' => $consignmentId]);
    $rowSales = $stmtSales->fetch();

    $grossRevenue = (float)($rowSales['total_revenue'] ?? 0.00);
    $commissionAmount = round($grossRevenue * ($commissionRate / 100), 2);

    $totalDeductions = $commissionAmount + $freightDeduction + $helperBattaDeduction + $coolieDeduction + $loanDeduction;
    $netPayable = round($grossRevenue - $totalDeductions, 2);

    if ($netPayable < 0) {
        throw new Exception("Deductions (" . formatLKR($totalDeductions) . ") exceed gross revenue (" . formatLKR($grossRevenue) . "). Please adjust loan deduction.");
    }

    $settlementNo = generateSettlementNo($db);

    // Insert Settlement
    $stmtSet = $db->prepare("
        INSERT INTO settlements
        (settlement_no, supplier_id, consignment_id, gross_revenue, commission_rate, commission_amount, freight_deduction, helper_batta_deduction, coolie_deduction, loan_deduction, net_payable, settlement_date, created_at)
        VALUES
        (:sno, :sup_id, :cid, :grev, :crate, :camount, :fded, :bded, :cded, :lded, :net, NOW(), NOW())
    ");

    $stmtSet->execute([
        ':sno'     => $settlementNo,
        ':sup_id'  => $consignment['supplier_id'],
        ':cid'     => $consignmentId,
        ':grev'    => $grossRevenue,
        ':crate'   => $commissionRate,
        ':camount' => $commissionAmount,
        ':fded'    => $freightDeduction,
        ':bded'    => $helperBattaDeduction,
        ':cded'    => $coolieDeduction,
        ':lded'    => $loanDeduction,
        ':net'     => $netPayable
    ]);

    $settlementId = $db->lastInsertId();

    // Update Supplier Loan Balance
    if ($loanDeduction > 0) {
        $newLoanBalance = max(0.00, $consignment['current_loan_balance'] - $loanDeduction);
        $stmtLoan = $db->prepare("UPDATE suppliers SET current_loan_balance = :nb WHERE id = :sid");
        $stmtLoan->execute([':nb' => $newLoanBalance, ':sid' => $consignment['supplier_id']]);
    }

    // Mark consignment as settled
    $stmtMarkC = $db->prepare("UPDATE consignments SET status = 'settled' WHERE id = :cid");
    $stmtMarkC->execute([':cid' => $consignmentId]);

    // Mark fish items as settled
    $stmtMarkF = $db->prepare("UPDATE fish_items SET status = 'settled' WHERE consignment_id = :cid AND status = 'sold'");
    $stmtMarkF->execute([':cid' => $consignmentId]);

    $db->commit();

    setFlash('success', "Settlement Voucher {$settlementNo} Generated! Net Payable to Boat Owner ({$consignment['supplier_name']}): " . formatLKR($netPayable));
    header("Location: ../index.php?page=settlement_voucher&id={$settlementId}");
    exit;

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('danger', 'Settlement Failed: ' . $e->getMessage());
    header('Location: ../index.php?page=settlement_calculate');
    exit;
}
