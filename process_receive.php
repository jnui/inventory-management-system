<?php
require_once 'auth_check.php';
require_once 'db_connection.php';
require_once 'lib/inventory_functions.php';

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

    // Calculate new received quantity
    $newReceivedQuantity = $order['received_quantity'] + $receivedQuantity;
    
    // Determine if order is fully received
    $isFullyReceived = $newReceivedQuantity >= $order['quantity_ordered'];
    $newStatusId = $isFullyReceived ? 4 : 2; // 4 for Complete, 2 for Ordered & Waiting

    $now = date('Y-m-d H:i:s');
    $inventoryNotes = "Received on $now\nOrder ID: $orderId\nPO Number: " . ($order['PO'] ?: 'N/A') . "\nQty Ordered: {$order['quantity_ordered']}\nReceived: $receivedQuantity\nTotal Received: $newReceivedQuantity\nReceipt notes: " . ($notes ?: 'N/A');

    // Apply inventory change via shared helper
    apply_inventory_change($pdo, (int)$itemId, (int)$receivedQuantity, 'receive', $inventoryNotes, 28);

    // Update order with new received quantity and status
    $stmt = $pdo->prepare("
        UPDATE order_history 
        SET received_quantity = ?,
            status_id = ?,
            notes = CONCAT(COALESCE(notes, ''), '\n\n', ?)
        WHERE id = ?
    ");
    $stmt->execute([$newReceivedQuantity, $newStatusId, $inventoryNotes, $orderId]);

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