<?php
require_once 'auth_check.php';
require_once 'db_connection.php';
require_once 'lib/inventory_functions.php';

// Expect POST of received_qty keyed by order_id
if (!isset($_POST['received_qty']) || !is_array($_POST['received_qty'])) {
    die('Invalid request');
}

$receivedQty = $_POST['received_qty'];
// Static employee ID for bulk receive operations
$employeeId = 28; // Bulk Receive System Employee

foreach ($receivedQty as $orderId => $qtyRec) {
    $orderId = (int) $orderId;
    $qtyRec = (int) $qtyRec;
    if ($qtyRec <= 0) {
        continue;
    }
    try {
        // Start transaction for each order line
        $pdo->beginTransaction();

        // Fetch the order line and item details
        $stmt = $pdo->prepare(
            "SELECT oh.*, cm.item_name, cm.whole_quantity as current_quantity
             FROM order_history oh
             JOIN consumable_materials cm ON oh.consumable_id = cm.id
             WHERE oh.id = ? AND oh.status_id = 2"
        );
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            throw new Exception("Order $orderId not found or already processed");
        }

        // Calculate new received quantity
        $newReceivedQuantity = $order['received_quantity'] + $qtyRec;
        
        // Determine if order is fully received
        $isFullyReceived = $newReceivedQuantity >= $order['quantity_ordered'];
        $newStatusId = $isFullyReceived ? 4 : 2; // 4 for Complete, 2 for Ordered & Waiting

        // Build notes for inventory change and order update
        $now = date('Y-m-d H:i:s');
        $inventoryNotes =
            "Received on $now\n" .
            "Order ID: $orderId\n" .
            "PO Number: " . ($order['PO'] ?: 'N/A') . "\n" .
            "Qty Ordered: {$order['quantity_ordered']}\n" .
            "Received: $qtyRec\n" .
            "Total Received: $newReceivedQuantity\n" .
            "Receipt notes: N/A";

        // Apply inventory change (same as process_receive)
        apply_inventory_change(
            $pdo,
            (int)$order['consumable_id'],
            $qtyRec,
            'receive',
            $inventoryNotes,
            $employeeId
        );

        // Update order with new received quantity and status
        $stmt = $pdo->prepare(
            "UPDATE order_history
             SET received_quantity = ?,
                 status_id = ?,
                 notes = CONCAT(COALESCE(notes, ''), '\n\n', ?)
             WHERE id = ?"
        );
        $stmt->execute([$newReceivedQuantity, $newStatusId, $inventoryNotes, $orderId]);

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die('Error processing order ' . $orderId . ': ' . $e->getMessage());
    }
}

// After processing all, redirect back
header('Location: receiving.php');
exit; 