<?php
// actions/billing/create_invoice.php
// POS Auction Sale, Credit Limit Check, Defaulter Freeze Guard & Security Gate Pass

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php?page=billing_pos');
    exit;
}

$buyerId     = intval($_POST['buyer_id'] ?? 0);
$paymentMode = sanitizeInput($_POST['payment_mode'] ?? 'cash');
$handlingFee = floatval($_POST['handling_fee'] ?? 0.00);
$cuttingFee  = floatval($_POST['cutting_fee'] ?? 0.00);
$itemsRaw    = $_POST['items'] ?? [];

if ($buyerId <= 0 || empty($itemsRaw)) {
    setFlash('danger', 'Please select a buyer and add at least one fish item to the bill.');
    header('Location: ../../index.php?page=billing_pos');
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();

    // 1. Validate Buyer & Check Defaulter Status
    $stmtBuyer = $db->prepare("SELECT id, name, credit_limit, current_credit_balance, status FROM buyers WHERE id = :id FOR UPDATE");
    $stmtBuyer->execute([':id' => $buyerId]);
    $buyer = $stmtBuyer->fetch();

    if (!$buyer) {
        throw new Exception("Selected buyer not found.");
    }
    if ($buyer['status'] === 'blocked') {
        throw new Exception("DEFAULTER FREEZE! Merchant '{$buyer['name']}' account is BLOCKED by Main Mudiyala. Bills cannot be issued.");
    }

    // 2. Validate Items & Compute Line Totals
    $subtotal = 0.00;
    $validatedItems = [];

    foreach ($itemsRaw as $item) {
        $fishId = intval($item['fish_id'] ?? 0);
        $unitPrice = floatval($item['unit_price'] ?? 0.00);

        if ($fishId <= 0 || $unitPrice <= 0) continue;

        $stmtFish = $db->prepare("SELECT id, tag_code, net_weight, status FROM fish_items WHERE id = :fid FOR UPDATE");
        $stmtFish->execute([':fid' => $fishId]);
        $fish = $stmtFish->fetch();

        if (!$fish || $fish['status'] !== 'available') {
            throw new Exception("Fish item tag '{$fish['tag_code']}' is no longer available.");
        }

        $weightKg = (float)$fish['net_weight'];
        $lineTotal = round($weightKg * $unitPrice, 2);
        $subtotal += $lineTotal;

        $validatedItems[] = [
            'fish_id'    => $fishId,
            'tag_code'   => $fish['tag_code'],
            'weight_kg'  => $weightKg,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal
        ];
    }

    if (empty($validatedItems)) {
        throw new Exception("No valid items found to generate invoice.");
    }

    $grandTotal = round($subtotal + $handlingFee + $cuttingFee, 2);

    // 3. Strict Credit Limit Enforcement
    if ($paymentMode === 'credit') {
        $newCreditBalance = $buyer['current_credit_balance'] + $grandTotal;
        if ($newCreditBalance > $buyer['credit_limit']) {
            $exceeded = formatLKR($newCreditBalance - $buyer['credit_limit']);
            throw new Exception("Credit Limit Exceeded! Buyer '{$buyer['name']}' limit is " . formatLKR($buyer['credit_limit']) . ". Current Balance: " . formatLKR($buyer['current_credit_balance']) . ". Bill Total: " . formatLKR($grandTotal) . ". Over limit by {$exceeded}.");
        }
    }

    // 4. Generate Invoice & Gate Pass Hash
    $invoiceNo = generateInvoiceNo($db);
    $securityHash = generateSecurityHash($invoiceNo, $grandTotal, $buyerId);

    $stmtInvoice = $db->prepare("
        INSERT INTO invoices 
        (invoice_no, buyer_id, payment_mode, subtotal, handling_fee, cutting_fee, grand_total, security_hash, issued_by, status, created_at)
        VALUES 
        (:inv_no, :buyer_id, :pmode, :subtotal, :hfee, :cfee, :gtotal, :hash, :user_id, 'paid', NOW())
    ");

    $stmtInvoice->execute([
        ':inv_no'    => $invoiceNo,
        ':buyer_id'  => $buyerId,
        ':pmode'     => $paymentMode,
        ':subtotal'  => $subtotal,
        ':hfee'      => $handlingFee,
        ':cfee'      => $cuttingFee,
        ':gtotal'    => $grandTotal,
        ':hash'      => $securityHash,
        ':user_id'   => $user['id']
    ]);

    $invoiceId = $db->lastInsertId();

    // 5. Insert Invoice Items & Update Fish Status
    $stmtItem = $db->prepare("INSERT INTO invoice_items (invoice_id, fish_item_id, weight_kg, unit_price, line_total) VALUES (:inv_id, :fish_id, :w, :u, :lt)");
    $stmtStatus = $db->prepare("UPDATE fish_items SET status = 'sold' WHERE id = :fish_id");

    foreach ($validatedItems as $v) {
        $stmtItem->execute([
            ':inv_id'  => $invoiceId,
            ':fish_id' => $v['fish_id'],
            ':w'       => $v['weight_kg'],
            ':u'       => $v['unit_price'],
            ':lt'      => $v['line_total']
        ]);
        $stmtStatus->execute([':fish_id' => $v['fish_id']]);
    }

    // 6. Update Buyer Credit Balance if Credit
    if ($paymentMode === 'credit') {
        $stmtUpdateCredit = $db->prepare("UPDATE buyers SET current_credit_balance = current_credit_balance + :gtotal WHERE id = :bid");
        $stmtUpdateCredit->execute([':gtotal' => $grandTotal, ':bid' => $buyerId]);
    }

    $db->commit();

    setFlash('success', "Invoice {$invoiceNo} Created Successfully! Exit Pass Verification Code: {$securityHash}");
    header("Location: ../../index.php?page=invoice_print&id={$invoiceId}");
    exit;

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('danger', 'POS Transaction Error: ' . $e->getMessage());
    header('Location: ../../index.php?page=billing_pos');
    exit;
}
