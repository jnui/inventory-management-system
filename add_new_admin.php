<?php
// Include database connection
require_once 'db_connection.php';

// New admin user details
$name = 'System Administrator';
$initials = 'SYSADMIN';
$password = 'admin123';
$role = 'admin';

// Generate password hash
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if this user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE initials = ?");
    $stmt->execute([$initials]);
    $userExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userExists) {
        // Update existing user
        $stmt = $pdo->prepare("UPDATE users SET name = ?, password = ?, role = ? WHERE initials = ?");
        $result = $stmt->execute([$name, $passwordHash, $role, $initials]);
        $message = "Admin user updated successfully.";
    } else {
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (name, initials, password, role) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$name, $initials, $passwordHash, $role]);
        $message = "New admin user created successfully.";
    }
    
    if ($result) {
        echo "<h1>Success</h1>";
        echo "<p>" . htmlspecialchars($message) . "</p>";
        echo "<p>You can now <a href='login.php'>login</a> with:</p>";
        echo "<ul>";
        echo "<li>Initials: " . htmlspecialchars($initials) . "</li>";
        echo "<li>Password: " . htmlspecialchars($password) . "</li>";
        echo "</ul>";
        
        // Verify the hash
        echo "<p>Password verification test: " . (password_verify($password, $passwordHash) ? "Passed" : "Failed") . "</p>";
    } else {
        echo "<h1>Error</h1>";
        echo "<p>Failed to create/update user.</p>";
    }
} catch (PDOException $e) {
    echo "<h1>Database Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 