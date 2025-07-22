<?php
require_once 'db_connection.php';
header('Content-Type: application/json');

if (!isset($_GET['po_number'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing PO number']);
    exit;
}

$po = $_GET['po_number'];

try {
    $stmt = $pdo->prepare(
        "SELECT 
            oh.id AS order_id,
            oh.consumable_id AS item_id,
            cm.item_name,
            oh.quantity_ordered,
            oh.received_quantity,
            oh.status_id,
            os.status_name
         FROM order_history oh
         JOIN consumable_materials cm ON oh.consumable_id = cm.id
         JOIN order_status os ON oh.status_id = os.id
         WHERE oh.PO = :po"
    );
    $stmt->execute(['po' => $po]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($items);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 