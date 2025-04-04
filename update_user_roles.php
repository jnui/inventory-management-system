<?php
require_once 'db_connection.php';

try {
    // Update the users table to add the read-only role
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin', 'readonly') NOT NULL DEFAULT 'user'");
    echo "Successfully updated users table schema\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 