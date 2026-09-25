<?php
// api/search_buyers.php
// JSON Endpoint to search buyers and return credit limit eligibility

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$query = sanitizeInput($_GET['q'] ?? '');
$buyerId = intval($_GET['id'] ?? 0);

try {
    $db = getDB();

    if ($buyerId > 0) {
        // Fetch specific buyer
        $stmt = $db->prepare("SELECT id, buyer_code, name, phone, credit_limit, current_credit_balance, status FROM buyers WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $buyerId]);
        $buyer = $stmt->fetch();
        if ($buyer) {
            $buyer['available_credit'] = max(0, $buyer['credit_limit'] - $buyer['current_credit_balance']);
            echo json_encode(['success' => true, 'buyer' => $buyer]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Buyer not found']);
        }
        exit;
    }

    // Search query
    $sql = "SELECT id, buyer_code, name, phone, credit_limit, current_credit_balance, status FROM buyers WHERE (name LIKE :q OR buyer_code LIKE :q OR phone LIKE :q) LIMIT 10";
    $stmt = $db->prepare($sql);
    $stmt->execute([':q' => "%{$query}%"]);
    $buyers = $stmt->fetchAll();

    foreach ($buyers as &$b) {
        $b['available_credit'] = max(0, $b['credit_limit'] - $b['current_credit_balance']);
    }

    echo json_encode(['success' => true, 'buyers' => $buyers]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
