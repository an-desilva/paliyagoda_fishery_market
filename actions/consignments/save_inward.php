<?php
// actions/consignments/save_inward.php
// Saves inward lorry arrival & issues instant Helper Batta cash voucher

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php?page=consignments_list');
    exit;
}

$supplierId   = intval($_POST['supplier_id'] ?? 0);
$lorryNumber  = sanitizeInput($_POST['lorry_number'] ?? '');
$harborOrigin = sanitizeInput($_POST['harbor_origin'] ?? '');
$crateCount   = intval($_POST['crate_count'] ?? 0);
$freightCost  = floatval($_POST['freight_cost'] ?? 0.00);
$helperBatta  = floatval($_POST['helper_batta'] ?? 0.00);
$coolieCharges= floatval($_POST['coolie_charges'] ?? 0.00);
$notes        = sanitizeInput($_POST['notes'] ?? '');

if ($supplierId <= 0 || empty($lorryNumber) || empty($harborOrigin)) {
    setFlash('danger', 'Supplier, Lorry Number, and Harbor of Origin are required fields.');
    header('Location: ../../index.php?page=consignments_new');
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();

    $consignmentNo = generateConsignmentNo($db);

    $stmt = $db->prepare("
        INSERT INTO consignments 
        (consignment_no, supplier_id, lorry_number, harbor_origin, crate_count, freight_cost, helper_batta, coolie_charges, notes, status, arrival_date)
        VALUES 
        (:cno, :supplier_id, :lorry, :harbor, :crates, :freight, :batta, :coolie, :notes, 'unloaded', NOW())
    ");

    $stmt->execute([
        ':cno'         => $consignmentNo,
        ':supplier_id' => $supplierId,
        ':lorry'       => $lorryNumber,
        ':harbor'      => $harborOrigin,
        ':crates'      => $crateCount,
        ':freight'     => $freightCost,
        ':batta'       => $helperBatta,
        ':coolie'      => $coolieCharges,
        ':notes'       => $notes
    ]);

    $consignmentId = $db->lastInsertId();
    $db->commit();

    setFlash('success', "Consignment {$consignmentNo} registered! Helper Batta cash voucher of " . formatLKR($helperBatta) . " issued for Lorry {$lorryNumber}.");
    header("Location: ../../index.php?page=grading_entry&consignment_id={$consignmentId}");
    exit;

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('danger', 'Failed to register consignment: ' . $e->getMessage());
    header('Location: ../../index.php?page=consignments_new');
    exit;
}
