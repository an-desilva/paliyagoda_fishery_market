<?php
// api/get_live_metrics.php
// Real-time market dashboard metrics JSON endpoint

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

try {
    $db = getDB();

    $stmtRev = $db->query("
        SELECT 
            COUNT(id) AS total_invoices,
            COALESCE(SUM(grand_total), 0) AS total_revenue,
            COALESCE(SUM(subtotal), 0) AS total_subtotal
        FROM invoices
        WHERE DATE(created_at) = CURDATE() AND status = 'paid'
    ");
    $revStats = $stmtRev->fetch();

    $stmtWeight = $db->query("
        SELECT COALESCE(SUM(ii.weight_kg), 0) AS total_kg
        FROM invoice_items ii
        JOIN invoices i ON ii.invoice_id = i.id
        WHERE DATE(i.created_at) = CURDATE() AND i.status = 'paid'
    ");
    $todayKg = (float)($stmtWeight->fetch()['total_kg'] ?? 0);

    $commRate = (float)getSetting('default_commission_rate', 6.00);
    $estCommission = round(((float)$revStats['total_subtotal']) * ($commRate / 100), 2);

    $stmtLorries = $db->query("SELECT COUNT(id) AS cnt FROM consignments WHERE status != 'settled'");
    $activeLorries = (int)($stmtLorries->fetch()['cnt'] ?? 0);

    $stmtCredit = $db->query("SELECT COALESCE(SUM(current_credit_balance), 0) AS total_credit FROM buyers WHERE current_credit_balance > 0");
    $uncollectedCredit = (float)($stmtCredit->fetch()['total_credit'] ?? 0);

    $stmtBatta = $db->query("SELECT COALESCE(SUM(helper_batta), 0) AS total_batta FROM consignments WHERE DATE(arrival_date) = CURDATE()");
    $todayBatta = (float)($stmtBatta->fetch()['total_batta'] ?? 0);

    echo json_encode([
        'success' => true,
        'metrics' => [
            'today_traded_kg' => $todayKg,
            'today_gross_revenue' => (float)$revStats['total_revenue'],
            'today_commission_yield' => $estCommission,
            'active_lorries_count' => $activeLorries,
            'total_uncollected_credit' => $uncollectedCredit,
            'today_helper_batta_paid' => $todayBatta,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'API Error: ' . $e->getMessage()]);
}
