<?php
require_once 'db_connection.php';
header('Content-Type: application/json');

$po_number = $_POST['po_number'] ?? null;

if (!$po_number) {
    http_response_code(400);
    echo json_encode(['error' => 'PO Number is required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT oh.id, c.item_name, oh.quantity_ordered, oh.notes
        FROM order_history oh
        JOIN consumable_materials c ON oh.consumable_id = c.id
        WHERE oh.PO = ?
        ORDER BY c.item_name
    ");
    $stmt->execute([$po_number]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($items);

} catch (PDOException $e) {
    http_response_code(500);
    error_log('DB Error in get_po_details.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred.']);
}
