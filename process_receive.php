<?php
require_once 'auth_check.php';
require_once 'db_connection.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Validate input
    if (!isset($_POST['orderId']) || !isset($_POST['itemId']) || !isset($_POST['receivedQuantity'])) {
        throw new Exception('Missing required parameters');
    }

    $orderId = $_POST['orderId'];
    $itemId = $_POST['itemId'];
    $receivedQuantity = intval($_POST['receivedQuantity']);
    $notes = $_POST['notes'] ?? '';

    if ($receivedQuantity < 0) {
        throw new Exception('Received quantity cannot be negative');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Get current order details
    $stmt = $pdo->prepare("
        SELECT oh.*, cm.item_name, cm.whole_quantity as current_quantity
        FROM order_history oh
        JOIN consumable_materials cm ON oh.consumable_id = cm.id
        WHERE oh.id = ? AND oh.status_id = 2
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found or already processed');
    }

    // Create inventory change entry
    $inventoryNotes = "Order ID: " . $orderId . "\n" .
                     "PO Number: " . ($order['PO'] ? $order['PO'] : 'N/A') . "\n" .
                     "Qty Ordered: " . $order['quantity_ordered'] . "\n" .
                     "Received: " . $receivedQuantity . "\n" .
                     "Previous stock: " . $order['current_quantity'] . "\n" .
                     "New stock: " . ($order['current_quantity'] + $receivedQuantity) . "\n" .
                     "Receipt notes: " . ($notes ? $notes : "N/A") . "\n" .
                     "Vendor invoice: N/A";  // We'll add this later

    $stmt = $pdo->prepare("
        INSERT INTO inventory_change_entries 
        (consumable_material_id, item_name, item_notes, items_added, whole_quantity, change_date)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$itemId, $order['item_name'], $inventoryNotes, $receivedQuantity, $order['current_quantity'] + $receivedQuantity]);

    // Update inventory quantity
    $stmt = $pdo->prepare("
        UPDATE consumable_materials 
        SET whole_quantity = whole_quantity + ?
        WHERE id = ?
    ");
    $stmt->execute([$receivedQuantity, $itemId]);

    // Mark order as complete (status_id = 4)
    $stmt = $pdo->prepare("
        UPDATE order_history 
        SET status_id = 4,
            notes = CONCAT(COALESCE(notes, ''), '\n\n', ?)
        WHERE id = ?
    ");
    $stmt->execute([$inventoryNotes, $orderId]);

    // Commit transaction
    $pdo->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Order received successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 