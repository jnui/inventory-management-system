<?php
// db_connection.php

$host    = 'localhost';
$db      = 'inventory';
$user    = 'johnny';       // Replace with your database username
$pass    = 'password';       // Replace with your database password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return results as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements if available
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Handle error appropriately in production code
    die('Database connection failed: ' . $e->getMessage());
}
?>
