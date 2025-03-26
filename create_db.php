<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Connect without database selected
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS smccontr_inventory");
    echo "Database created successfully\n";
    
    // Select the database
    $pdo->exec("USE smccontr_inventory");
    
    // Create employees table
    $pdo->exec("CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL
    )");
    
    // Create consumable_materials table
    $pdo->exec("CREATE TABLE IF NOT EXISTS consumable_materials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_name VARCHAR(100) NOT NULL,
        item_type VARCHAR(50)
    )");
    
    // Insert some test data
    $pdo->exec("INSERT INTO employees (first_name) VALUES ('Phil'), ('Vicente')");
    
    $items = [
        "12 x 12 Tee",
        "12 inch, 90",
        "15 inch 45 ribbed",
        "15 inch 45",
        "15 inch ribbed 45",
        "15 inch 45 solid",
        "15 inch 45 ribbed solid"
    ];
    
    $stmt = $pdo->prepare("INSERT INTO consumable_materials (item_name) VALUES (?)");
    foreach ($items as $item) {
        $stmt->execute([$item]);
    }
    
    echo "Tables and test data created successfully\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 