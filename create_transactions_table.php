<?php
require_once 'db_connection.php';

try {
    // Create inventory_transactions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS inventory_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            consumable_material_id INT NOT NULL,
            quantity INT NOT NULL,
            inventory_action ENUM('add', 'remove') NOT NULL,
            employee_id INT NOT NULL,
            transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (consumable_material_id) REFERENCES consumable_materials(id),
            FOREIGN KEY (employee_id) REFERENCES employees(id)
        )
    ");
    
    echo "Successfully created inventory_transactions table.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 