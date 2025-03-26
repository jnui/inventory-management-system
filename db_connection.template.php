<?php
// db_connection.template.php
// Copy this file to db_connection.php and update with your credentials

// Database credentials - update these with your web server's credentials
$host    = getenv('DB_HOST') ?: 'localhost';
$db      = getenv('DB_NAME') ?: 'your_database_name';
$user    = getenv('DB_USER') ?: 'your_username';
$pass    = getenv('DB_PASS') ?: 'your_password';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Create order_status table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_status (
        id INT PRIMARY KEY AUTO_INCREMENT,
        status_name VARCHAR(50) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create order_history table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_history (
        id INT PRIMARY KEY AUTO_INCREMENT,
        consumable_id INT NOT NULL,
        status_id INT NOT NULL,
        quantity_ordered INT NOT NULL,
        notes TEXT,
        ordered_by VARCHAR(100) NOT NULL,
        ordered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (consumable_id) REFERENCES consumable_materials(id),
        FOREIGN KEY (status_id) REFERENCES order_status(id)
    )");
    
    // Insert default order statuses if they don't exist
    $pdo->exec("INSERT IGNORE INTO order_status (id, status_name, description) VALUES 
        (1, 'Not Ordered', 'Item needs to be ordered'),
        (2, 'Ordered & Waiting', 'Order has been placed and is waiting for delivery'),
        (3, 'Backordered', 'Item is backordered by supplier'),
        (4, 'Complete', 'Order has been received and inventory updated')
    ");
    
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
} 