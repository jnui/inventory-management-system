<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db_connection.php';

try {
    $stmt = $pdo->query("SELECT item_name FROM consumable_materials WHERE item_name LIKE '%15 inch%'");
    $items = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($items);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 