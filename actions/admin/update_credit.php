<?php
// actions/admin/update_credit.php
// Updates buyer credit limit & toggles defaulter freeze status (active vs blocked)

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php?page=admin_buyers_credit');
    exit;
}

$buyerId     = intval($_POST['buyer_id'] ?? 0);
$creditLimit = floatval($_POST['credit_limit'] ?? 0.00);
$status      = sanitizeInput($_POST['status'] ?? 'active');

if ($buyerId <= 0) {
    setFlash('danger', 'Valid buyer is required.');
    header('Location: ../../index.php?page=admin_buyers_credit');
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("UPDATE buyers SET credit_limit = :climit, status = :status WHERE id = :bid");
    $stmt->execute([
        ':climit' => $creditLimit,
        ':status' => $status,
        ':bid'    => $buyerId
    ]);

    $statusMsg = $status === 'blocked' ? 'BLOCKED (Defaulter Freeze)' : 'ACTIVE';
    setFlash('success', "Buyer Credit Limit updated to " . formatLKR($creditLimit) . " and status set to {$statusMsg}.");
    header('Location: ../../index.php?page=admin_buyers_credit');
    exit;

} catch (Exception $e) {
    setFlash('danger', 'Failed to update buyer credit: ' . $e->getMessage());
    header('Location: ../../index.php?page=admin_buyers_credit');
    exit;
}
