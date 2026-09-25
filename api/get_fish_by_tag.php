<?php
// api/get_fish_by_tag.php
// JSON Endpoint to lookup graded fish item by barcode/tag code

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$tagCode = sanitizeInput($_GET['tag'] ?? '');

if (empty($tagCode)) {
    echo json_encode(['success' => false, 'message' => 'Tag code is required']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT 
            f.id,
            f.tag_code,
            f.species,
            f.gross_weight,
            f.tare_weight,
            f.net_weight,
            f.grade,
            f.status,
            c.consignment_no,
            c.harbor_origin,
            s.name AS supplier_name,
            s.boat_name
        FROM fish_items f
        JOIN consignments c ON f.consignment_id = c.id
        JOIN suppliers s ON c.supplier_id = s.id
        WHERE f.tag_code = :tag
        LIMIT 1
    ");
    $stmt->execute([':tag' => $tagCode]);
    $fish = $stmt->fetch();

    if ($fish) {
        if ($fish['status'] !== 'available') {
            echo json_encode([
                'success' => false,
                'message' => "Fish tag {$tagCode} is already " . strtoupper($fish['status'])
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'fish' => $fish
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Tag code '{$tagCode}' not found in active inventory"
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
