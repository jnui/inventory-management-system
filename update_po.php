<?php
require_once 'db_connection.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? null;
$po_number = $_POST['po_number'] ?? null;

if (!$action) {
    http_response_code(400);
    echo json_encode(['error' => 'Action is required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    switch ($action) {
        case 'update':
            if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
                foreach ($_POST['quantity'] as $order_id => $quantity) {
                    $notes = $_POST['notes'][$order_id] ?? '';
                    $stmt = $pdo->prepare("UPDATE order_history SET quantity_ordered = ?, notes = ? WHERE id = ?");
                    $stmt->execute([$quantity, $notes, $order_id]);
                }
            }
            break;

        case 'remove':
            $order_id = $_POST['order_id'] ?? null;
            if ($order_id) {
                $stmt = $pdo->prepare("DELETE FROM order_history WHERE id = ?");
                $stmt->execute([$order_id]);
            } else {
                throw new Exception("Order ID is required for removal.");
            }
            break;

        case 'add':
            $consumable_id = $_POST['consumable_id'] ?? null;
            $quantity = $_POST['quantity'] ?? null;
            $notes = $_POST['notes'] ?? '';
            $ordered_by = $_SESSION['username'] ?? 'System User';

            if ($po_number && $consumable_id && $quantity) {
                $stmt = $pdo->prepare(
                    "INSERT INTO order_history (consumable_id, status_id, quantity_ordered, notes, ordered_by, PO)
                     VALUES (?, 2, ?, ?, ?, ?)" // Default status 2: Ordered & Waiting
                );
                $stmt->execute([$consumable_id, $quantity, $notes, $ordered_by, $po_number]);
            } else {
                throw new Exception("Missing required fields for adding an item.");
            }
            break;

        default:
            throw new Exception("Invalid action specified.");
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log('Error in update_po.php: ' . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
