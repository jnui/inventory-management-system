<?php
require_once 'db_connection.php';

try {
    $columns = $pdo->query("DESCRIBE inventory_change_entries")->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in inventory_change_entries table:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 