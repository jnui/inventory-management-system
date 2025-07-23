<?php
// Disable verbose logging - only log default PHP errors
error_reporting(E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_PARSE | E_USER_ERROR | E_RECOVERABLE_ERROR);
ini_set('log_errors', '1');
ini_set('display_errors', '0');

// Database credentials - update these with your web server's credentials
$host    = getenv('DB_HOST') ?: 'localhost';
$db      = getenv('DB_NAME') ?: 'smccontr_inventory';
$user    = getenv('DB_USER') ?: 'smccontr_johnny';
$pass    = getenv('DB_PASS') ?: '9+p4v?*%b!fL';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
} 