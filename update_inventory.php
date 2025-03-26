<?php
require_once 'db_connection.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Validate input
    if (!isset($_POST['consumable_id']) || !isset($_POST['quantity_change'])) {
        throw new Exception('Missing required parameters');
    }

    $consumable_id = $_POST['consumable_id'];
    $quantity_change = $_POST['quantity_change'];
    $notes = $_POST['notes'] ?? '';
    $delivery_date = $_POST['delivery_date'] ?? date('Y-m-d');
    $received_by = $_POST['received_by'] ?? 'System';

    // Start transaction
    $pdo->beginTransaction();

    // Get current order status and details
    $stmt = $pdo->prepare("
        SELECT oh.status_id, oh.id as order_id, oh.quantity_ordered, oh.ordered_at,
               cm.item_name, cm.whole_quantity as current_quantity
        FROM order_history oh
        JOIN consumable_materials cm ON oh.consumable_id = cm.id
        WHERE oh.consumable_id = ?
        AND oh.id = (
            SELECT MAX(id)
            FROM order_history
            WHERE consumable_id = ?
        )
    ");
    $stmt->execute([$consumable_id, $consumable_id]);
    $order_status = $stmt->fetch(PDO::FETCH_ASSOC);

    // Update inventory
    $stmt = $pdo->prepare("
        UPDATE consumable_materials 
        SET whole_quantity = whole_quantity + ?
        WHERE id = ?
    ");
    $stmt->execute([$quantity_change, $consumable_id]);

    // If there was an active order (status 2 or 3), mark it as complete
    if ($order_status && in_array($order_status['status_id'], [2, 3])) {
        // Create detailed completion notes
        $completion_notes = sprintf(
            "Order completed on %s:\n" .
            "- Item: %s\n" .
            "- Quantity Received: %d\n" .
            "- Previous Quantity: %d\n" .
            "- New Quantity: %d\n" .
            "- Received By: %s\n" .
            "- Original Order Date: %s\n" .
            "- Original Order Quantity: %d\n" .
            "Additional Notes: %s",
            date('Y-m-d H:i:s'),
            $order_status['item_name'],
            $quantity_change,
            $order_status['current_quantity'],
            $order_status['current_quantity'] + $quantity_change,
            $received_by,
            date('Y-m-d', strtotime($order_status['ordered_at'])),
            $order_status['quantity_ordered'],
            $notes
        );

        $stmt = $pdo->prepare("
            INSERT INTO order_history 
            (consumable_id, status_id, quantity_ordered, notes, ordered_by, ordered_at)
            VALUES (?, 4, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $consumable_id,
            $quantity_change,
            $completion_notes,
            $received_by,
            $delivery_date
        ]);
    }

    // Commit transaction
    $pdo->commit();

    // Return success response with order status info
    echo json_encode([
        'success' => true,
        'message' => 'Inventory updated successfully',
        'order_status' => $order_status ? [
            'status_id' => $order_status['status_id'],
            'status_name' => $order_status['status_id'] == 4 ? 'Complete' : 
                           ($order_status['status_id'] == 2 ? 'Ordered & Waiting' : 
                           ($order_status['status_id'] == 3 ? 'Backordered' : 'Not Ordered')),
            'was_active' => $order_status && in_array($order_status['status_id'], [2, 3])
        ] : null
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