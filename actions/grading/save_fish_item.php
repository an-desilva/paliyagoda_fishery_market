<?php
// actions/grading/save_fish_item.php
// Action controller to record individual graded fish item & generate unique barcode tag

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php?page=grading_entry');
    exit;
}

$consignmentId = intval($_POST['consignment_id'] ?? 0);
$species       = sanitizeInput($_POST['species'] ?? '');
$grossWeight   = floatval($_POST['gross_weight'] ?? 0.00);
$tareWeight    = floatval($_POST['tare_weight'] ?? 0.00);
$grade         = sanitizeInput($_POST['grade'] ?? 'B_local');

if ($consignmentId <= 0 || empty($species) || $grossWeight <= 0) {
    setFlash('danger', 'Valid consignment, species, and positive gross weight are required.');
    header("Location: ../../index.php?page=grading_entry&consignment_id={$consignmentId}");
    exit;
}

$netWeight = max(0.1, $grossWeight - $tareWeight);

try {
    $db = getDB();
    $db->beginTransaction();

    $tagCode = generateTagCode();

    $stmt = $db->prepare("
        INSERT INTO fish_items 
        (tag_code, consignment_id, species, gross_weight, tare_weight, net_weight, grade, status, created_at)
        VALUES 
        (:tag, :cid, :species, :gross, :tare, :net, :grade, 'available', NOW())
    ");

    $stmt->execute([
        ':tag'     => $tagCode,
        ':cid'     => $consignmentId,
        ':species' => $species,
        ':gross'   => $grossWeight,
        ':tare'    => $tareWeight,
        ':net'     => $netWeight,
        ':grade'   => $grade
    ]);

    // Update consignment status to 'auctioning' if it was 'unloaded' or 'graded'
    $stmtUpdate = $db->prepare("UPDATE consignments SET status = 'auctioning' WHERE id = :cid AND status IN ('unloaded', 'graded')");
    $stmtUpdate->execute([':cid' => $consignmentId]);

    $db->commit();

    setFlash('success', "Fish Item Tagged Successfully! Tag: {$tagCode} ({$species}, Net: " . formatKg($netWeight) . ", Grade: {$grade})");
    header("Location: ../../index.php?page=grading_entry&consignment_id={$consignmentId}");
    exit;

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('danger', 'Failed to record fish item: ' . $e->getMessage());
    header("Location: ../../index.php?page=grading_entry&consignment_id={$consignmentId}");
    exit;
}
