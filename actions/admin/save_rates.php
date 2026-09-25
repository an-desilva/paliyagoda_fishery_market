<?php
// actions/admin/save_rates.php
// Updates dynamic commission % and market floor fee settings

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php?page=admin_settings_commission');
    exit;
}

$commRate     = floatval($_POST['default_commission_rate'] ?? 6.00);
$handlingFee  = floatval($_POST['default_handling_fee'] ?? 250.00);
$helperBatta  = floatval($_POST['default_helper_batta'] ?? 3500.00);
$coolieCharge = floatval($_POST['default_coolie_charge'] ?? 2400.00);

try {
    $db = getDB();
    $db->beginTransaction();

    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = :v");

    $settingsMap = [
        'default_commission_rate' => $commRate,
        'default_handling_fee'     => $handlingFee,
        'default_helper_batta'     => $helperBatta,
        'default_coolie_charge'    => $coolieCharge,
    ];

    foreach ($settingsMap as $k => $v) {
        $stmt->execute([':k' => $k, ':v' => (string)$v]);
    }

    $db->commit();

    setFlash('success', 'System Commission & Fee Settings updated successfully!');
    header('Location: ../../index.php?page=admin_settings_commission');
    exit;

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('danger', 'Failed to update settings: ' . $e->getMessage());
    header('Location: ../../index.php?page=admin_settings_commission');
    exit;
}
