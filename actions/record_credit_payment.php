<?php
// actions/record_credit_payment.php
// Action to record buyer credit payment recovery

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=credit_ledger');
    exit;
}

$buyerId       = intval($_POST['buyer_id'] ?? 0);
$amountPaid    = floatval($_POST['amount_paid'] ?? 0.00);
$paymentMethod = sanitizeInput($_POST['payment_method'] ?? 'Cash');
$notes         = sanitizeInput($_POST['notes'] ?? '');

if ($buyerId <= 0 || $amountPaid <= 0) {
    setFlash('danger', 'Valid buyer and positive payment amount are required.');
    header('Location: ../index.php?page=credit_ledger');
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();

    $stmtBuyer = $db->prepare("SELECT id, name, current_credit_balance FROM buyers WHERE id = :id FOR UPDATE");
    $stmtBuyer->execute([':id' => $buyerId]);
    $buyer = $stmtBuyer->fetch();

    if (!$buyer) {
        throw new Exception("Buyer not found.");
    }

    // Insert Credit Payment Record
    $stmtPay = $db->prepare("
        INSERT INTO credit_payments 
        (buyer_id, amount_paid, payment_method, notes, received_by, payment_date)
        VALUES 
        (:bid, :amount, :method, :notes, :uid, NOW())
    ");
    $stmtPay->execute([
        ':bid'    => $buyerId,
        ':amount' => $amountPaid,
        ':method' => $paymentMethod,
        ':notes'  => $notes,
        ':uid'    => $user['id']
    ]);

    // Deduct from Buyer's Credit Balance (floor at 0.00)
    $newBalance = max(0.00, $buyer['current_credit_balance'] - $amountPaid);
    $stmtUpdate = $db->prepare("UPDATE buyers SET current_credit_balance = :nb WHERE id = :bid");
    $stmtUpdate->execute([':nb' => $newBalance, ':bid' => $buyerId]);

    // If balance returned below credit limit, unblock buyer if blocked
    $stmtUnblock = $db->prepare("UPDATE buyers SET status = 'active' WHERE id = :bid AND current_credit_balance <= credit_limit");
    $stmtUnblock->execute([':bid' => $buyerId]);

    $db->commit();

    setFlash('success', "Payment of " . formatLKR($amountPaid) . " recorded for {$buyer['name']}. New Credit Balance: " . formatLKR($newBalance));
    header('Location: ../index.php?page=credit_ledger');
    exit;

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('danger', 'Failed to record credit payment: ' . $e->getMessage());
    header('Location: ../index.php?page=credit_ledger');
    exit;
}
