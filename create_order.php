<?php
require_once 'db_connection.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Validate input
    if (!isset($_POST['consumable_id']) || !isset($_POST['status_id']) || !isset($_POST['quantity_ordered'])) {
        throw new Exception('Missing required parameters');
    }

    $consumable_id = $_POST['consumable_id'];
    $status_id = $_POST['status_id'];
    $quantity_ordered = $_POST['quantity_ordered'];
    $notes = $_POST['notes'] ?? '';
    $ordered_by = $_POST['ordered_by'] ?? 'System';
    $po_number = $_POST['po_number'] ?? null;

    // Start transaction
    $pdo->beginTransaction();

    // Create new order record
    $stmt = $pdo->prepare("
        INSERT INTO order_history 
        (consumable_id, status_id, quantity_ordered, notes, ordered_by, ordered_at, PO)
        VALUES (?, ?, ?, ?, ?, NOW(), ?)
    ");
    $stmt->execute([
        $consumable_id,
        $status_id,
        $quantity_ordered,
        $notes,
        $ordered_by,
        $po_number
    ]);

    // Commit transaction
    $pdo->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'New order created successfully'
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