<?php
// Include database connection
require_once 'db_connection.php';

echo "<h1>Database Check</h1>";

try {
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $tableExists = $stmt->rowCount() > 0;
    
    echo "<p>Users table exists: " . ($tableExists ? "Yes" : "No") . "</p>";
    
    if ($tableExists) {
        // Check if admin user exists
        $stmt = $pdo->query("SELECT id, name, initials, role FROM users WHERE initials = 'ADMIN'");
        $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($adminUser) {
            echo "<p>Admin user found:</p>";
            echo "<ul>";
            echo "<li>ID: " . htmlspecialchars($adminUser['id']) . "</li>";
            echo "<li>Name: " . htmlspecialchars($adminUser['name']) . "</li>";
            echo "<li>Initials: " . htmlspecialchars($adminUser['initials']) . "</li>";
            echo "<li>Role: " . htmlspecialchars($adminUser['role']) . "</li>";
            echo "</ul>";
            
            // Check password hash format
            $stmt = $pdo->query("SELECT password FROM users WHERE initials = 'ADMIN'");
            $passwordHash = $stmt->fetchColumn();
            echo "<p>Password hash: " . htmlspecialchars($passwordHash) . "</p>";
            echo "<p>Password hash length: " . strlen($passwordHash) . "</p>";
            echo "<p>Password hash format valid: " . (strpos($passwordHash, '$2y$') === 0 ? "Yes" : "No") . "</p>";
            
            // Test if the password 'admin123' works with this hash
            echo "<p>Password 'admin123' verifies with hash: " . (password_verify('admin123', $passwordHash) ? "Yes" : "No") . "</p>";
        } else {
            echo "<p>Admin user not found.</p>";
        }
        
        // Show all users
        $stmt = $pdo->query("SELECT id, name, initials, role FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($users) > 0) {
            echo "<h2>All Users:</h2>";
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Name</th><th>Initials</th><th>Role</th></tr>";
            
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['initials']) . "</td>";
                echo "<td>" . htmlspecialchars($user['role']) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No users found in the database.</p>";
        }
    }
} catch (PDOException $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 