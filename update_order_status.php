<?php
require_once 'db_connection.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Validate input
    if (!isset($_POST['order_id']) || !isset($_POST['status_id'])) {
        throw new Exception('Missing required parameters');
    }

    $order_id = $_POST['order_id'];
    $status_id = $_POST['status_id'];
    $notes = $_POST['notes'] ?? '';

    // Start transaction
    $pdo->beginTransaction();

    // Get order details
    $stmt = $pdo->prepare("
        SELECT oh.*, cm.item_name
        FROM order_history oh
        JOIN consumable_materials cm ON oh.consumable_id = cm.id
        WHERE oh.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found');
    }

    // Create status update record
    $stmt = $pdo->prepare("
        INSERT INTO order_history 
        (consumable_id, status_id, quantity_ordered, notes, ordered_by, ordered_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $order['consumable_id'],
        $status_id,
        $order['quantity_ordered'],
        $notes,
        $_POST['ordered_by'] ?? 'System'
    ]);

    // Commit transaction
    $pdo->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully'
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