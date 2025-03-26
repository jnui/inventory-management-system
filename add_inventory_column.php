<?php
require_once 'db_connection.php';

try {
    // Add current_inventory column if it doesn't exist
    $pdo->exec("ALTER TABLE consumable_materials ADD COLUMN IF NOT EXISTS current_inventory INT DEFAULT 0");
    
    // Update current_inventory based on existing transactions
    $pdo->exec("
        UPDATE consumable_materials cm
        SET current_inventory = (
            SELECT COALESCE(SUM(
                CASE 
                    WHEN inventory_action = 'add' THEN quantity
                    WHEN inventory_action = 'remove' THEN -quantity
                END
            ), 0)
            FROM inventory_transactions
            WHERE consumable_material_id = cm.id
        )
    ");
    
    echo "Successfully added and initialized current_inventory column.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 