<?php
// Include database connection
require_once 'db_connection.php';

// Generate a new password hash for 'admin123'
$password = 'admin123';
$newHash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Update the admin user's password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE initials = ?");
    $result = $stmt->execute([$newHash, 'ADMIN']);
    
    if ($result) {
        echo "<h1>Admin Password Updated</h1>";
        echo "<p>The password for the ADMIN user has been updated successfully.</p>";
        echo "<p>New password hash: " . htmlspecialchars($newHash) . "</p>";
        
        // Verify the new hash
        echo "<p>Password 'admin123' verifies with new hash: " . (password_verify('admin123', $newHash) ? "Yes" : "No") . "</p>";
        
        echo "<p>You can now <a href='login.php'>login</a> with:</p>";
        echo "<ul>";
        echo "<li>Initials: ADMIN</li>";
        echo "<li>Password: admin123</li>";
        echo "</ul>";
    } else {
        echo "<h1>Error</h1>";
        echo "<p>Failed to update the admin password.</p>";
    }
} catch (PDOException $e) {
    echo "<h1>Database Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 