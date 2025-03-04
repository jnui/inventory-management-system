<?php
// Include database connection
require_once 'db_connection.php';

echo "<h1>Login Debug Tool</h1>";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $initials = $_POST['initials'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h2>Login Attempt</h2>";
    echo "<p>Initials: " . htmlspecialchars($initials) . "</p>";
    echo "<p>Password: " . htmlspecialchars($password) . "</p>";
    
    try {
        // Find user by initials
        $stmt = $pdo->prepare("SELECT id, name, initials, password, role FROM users WHERE initials = ?");
        $stmt->execute([$initials]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<h3>User Found</h3>";
            echo "<ul>";
            echo "<li>ID: " . htmlspecialchars($user['id']) . "</li>";
            echo "<li>Name: " . htmlspecialchars($user['name']) . "</li>";
            echo "<li>Initials: " . htmlspecialchars($user['initials']) . "</li>";
            echo "<li>Role: " . htmlspecialchars($user['role']) . "</li>";
            echo "</ul>";
            
            echo "<p>Stored Password Hash: " . htmlspecialchars($user['password']) . "</p>";
            echo "<p>Password Hash Length: " . strlen($user['password']) . "</p>";
            
            // Verify password
            $passwordVerified = password_verify($password, $user['password']);
            echo "<p>Password Verification Result: " . ($passwordVerified ? "Success" : "Failed") . "</p>";
            
            // Check if password needs rehash
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                echo "<p>Password hash needs to be updated to a newer algorithm.</p>";
            }
            
            // Generate a new hash for comparison
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            echo "<p>New Hash for Same Password: " . htmlspecialchars($newHash) . "</p>";
            echo "<p>New Hash Verifies: " . (password_verify($password, $newHash) ? "Yes" : "No") . "</p>";
            
        } else {
            echo "<h3>User Not Found</h3>";
            echo "<p>No user found with initials: " . htmlspecialchars($initials) . "</p>";
        }
    } catch (PDOException $e) {
        echo "<h3>Database Error</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

<h2>Test Login</h2>
<form method="post" action="">
    <div>
        <label for="initials">Initials:</label>
        <input type="text" id="initials" name="initials" required>
    </div>
    <div style="margin-top: 10px;">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
    </div>
    <div style="margin-top: 15px;">
        <button type="submit">Test Login</button>
    </div>
</form>

<h2>Available Users</h2>
<?php
try {
    $stmt = $pdo->query("SELECT id, name, initials, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
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
} catch (PDOException $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 