<?php
// Include database connection
require_once 'db_connection.php';

echo "<h1>Create/Update Admin User</h1>";

try {
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Create users table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                initials VARCHAR(10) NOT NULL,
                password VARCHAR(255) NOT NULL,
                role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        echo "<p>Users table created successfully.</p>";
    }
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE initials = ?");
    $stmt->execute(['ADMIN']);
    $adminExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Generate a new password hash
    $password = 'admin123'; // Default password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    if ($adminExists) {
        // Update existing admin user
        $stmt = $pdo->prepare("UPDATE users SET name = ?, password = ?, role = ? WHERE initials = ?");
        $stmt->execute(['Administrator', $passwordHash, 'admin', 'ADMIN']);
        echo "<p>Admin user updated successfully.</p>";
    } else {
        // Create new admin user
        $stmt = $pdo->prepare("INSERT INTO users (name, initials, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Administrator', 'ADMIN', $passwordHash, 'admin']);
        echo "<p>Admin user created successfully.</p>";
    }
    
    echo "<p>Admin user details:</p>";
    echo "<ul>";
    echo "<li>Initials: ADMIN</li>";
    echo "<li>Password: admin123</li>";
    echo "<li>Role: admin</li>";
    echo "</ul>";
    
    echo "<p>Password hash: " . htmlspecialchars($passwordHash) . "</p>";
    echo "<p>You can now <a href='login.php'>login</a> with these credentials.</p>";
    
} catch (PDOException $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 