<?php
// actions/admin/manage_loans.php
// Issues advance loans & cash disbursements to boat owners

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$user = requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php?page=admin_suppliers_loans');
    exit;
}

$supplierId = intval($_POST['supplier_id'] ?? 0);
$amount     = floatval($_POST['amount'] ?? 0.00);
$type       = sanitizeInput($_POST['type'] ?? 'disbursement');
$notes      = sanitizeInput($_POST['notes'] ?? '');

if ($supplierId <= 0 || $amount <= 0) {
    setFlash('danger', 'Valid boat owner and positive loan amount are required.');
    header('Location: ../../index.php?page=admin_suppliers_loans');
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();

    $stmtSup = $db->prepare("SELECT id, name, current_loan_balance FROM suppliers WHERE id = :sid FOR UPDATE");
    $stmtSup->execute([':sid' => $supplierId]);
    $supplier = $stmtSup->fetch();

    if (!$supplier) {
        throw new Exception("Boat owner supplier not found.");
    }

    $stmtLog = $db->prepare("
        INSERT INTO supplier_loans (supplier_id, amount, type, notes, issued_by, created_at)
        VALUES (:sid, :amt, :type, :notes, :uid, NOW())
    ");
    $stmtLog->execute([
        ':sid'   => $supplierId,
        ':amt'   => $amount,
        ':type'  => $type,
        ':notes' => $notes,
        ':uid'   => $user['id']
    ]);

    if ($type === 'disbursement') {
        $newBalance = $supplier['current_loan_balance'] + $amount;
    } else {
        $newBalance = max(0.00, $supplier['current_loan_balance'] - $amount);
    }

    $stmtUpdate = $db->prepare("UPDATE suppliers SET current_loan_balance = :nb WHERE id = :sid");
    $stmtUpdate->execute([':nb' => $newBalance, ':sid' => $supplierId]);

    $db->commit();

    setFlash('success', "Loan advance of " . formatLKR($amount) . " logged for {$supplier['name']}. New Loan Balance: " . formatLKR($newBalance));
    header('Location: ../../index.php?page=admin_suppliers_loans');
    exit;

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('danger', 'Loan transaction failed: ' . $e->getMessage());
    header('Location: ../../index.php?page=admin_suppliers_loans');
    exit;
}
