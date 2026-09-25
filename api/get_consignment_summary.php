<?php
// api/get_consignment_summary.php
// JSON Endpoint to fetch summary of a consignment for settlement calculations

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$consignmentId = intval($_GET['id'] ?? 0);

if ($consignmentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Consignment ID']);
    exit;
}

try {
    $db = getDB();

    // Fetch consignment + supplier info
    $stmt = $db->prepare("
        SELECT 
            c.*,
            s.name AS supplier_name,
            s.supplier_code,
            s.boat_name,
            s.current_loan_balance
        FROM consignments c
        JOIN suppliers s ON c.supplier_id = s.id
        WHERE c.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $consignmentId]);
    $consignment = $stmt->fetch();

    if (!$consignment) {
        echo json_encode(['success' => false, 'message' => 'Consignment not found']);
        exit;
    }

    // Fetch sales revenue for this consignment
    $stmtSales = $db->prepare("
        SELECT 
            COUNT(DISTINCT f.id) AS total_fish_count,
            SUM(CASE WHEN f.status IN ('sold', 'settled') THEN 1 ELSE 0 END) AS sold_fish_count,
            SUM(CASE WHEN f.status IN ('sold', 'settled') THEN f.net_weight ELSE 0 END) AS total_sold_weight,
            SUM(CASE WHEN f.status IN ('sold', 'settled') THEN (ii.line_total) ELSE 0 END) AS gross_sales_revenue
        FROM fish_items f
        LEFT JOIN invoice_items ii ON f.id = ii.fish_item_id
        WHERE f.consignment_id = :cid
    ");
    $stmtSales->execute([':cid' => $consignmentId]);
    $salesSummary = $stmtSales->fetch();

    $grossRevenue = (float)($salesSummary['gross_sales_revenue'] ?? 0.00);
    $defaultCommissionRate = DEFAULT_COMMISSION_RATE;
    $commissionAmount = round($grossRevenue * ($defaultCommissionRate / 100), 2);
    
    $freightCost = (float)$consignment['freight_cost'];
    $helperBatta = (float)$consignment['helper_batta'];
    $coolieCharges = (float)$consignment['coolie_charges'];
    $supplierLoanBalance = (float)$consignment['current_loan_balance'];

    $totalDeductionsWithoutLoan = $commissionAmount + $freightCost + $helperBatta + $coolieCharges;
    $estimatedNetPayable = max(0, $grossRevenue - $totalDeductionsWithoutLoan);

    echo json_encode([
        'success' => true,
        'consignment' => $consignment,
        'sales_summary' => [
            'total_fish_count' => (int)$salesSummary['total_fish_count'],
            'sold_fish_count' => (int)$salesSummary['sold_fish_count'],
            'total_sold_weight' => (float)$salesSummary['total_sold_weight'],
            'gross_sales_revenue' => $grossRevenue,
            'default_commission_rate' => $defaultCommissionRate,
            'commission_amount' => $commissionAmount,
            'freight_cost' => $freightCost,
            'helper_batta' => $helperBatta,
            'coolie_charges' => $coolieCharges,
            'supplier_loan_balance' => $supplierLoanBalance,
            'estimated_net_payable' => $estimatedNetPayable
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
