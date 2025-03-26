<?php
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $consumable_id = $_POST['consumable_id'];
        $status_id = $_POST['status_id'];
        $quantity_ordered = $_POST['quantity_ordered'];
        $notes = $_POST['notes'];
        $ordered_by = 'System User'; // You might want to get this from a session variable

        // Insert new order record
        $stmt = $pdo->prepare("
            INSERT INTO order_history 
            (consumable_id, status_id, quantity_ordered, notes, ordered_by) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $consumable_id,
            $status_id,
            $quantity_ordered,
            $notes,
            $ordered_by
        ]);

        // Redirect back to ordering page with success message
        header("Location: ordering.php?success=1");
        exit;
    } catch (PDOException $e) {
        // Redirect back with error message
        header("Location: ordering.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    // If not POST request, redirect to ordering page
    header("Location: ordering.php");
    exit;
}
?> 