<?php
// Include authentication check
require_once 'auth_check.php';

// Check if user has admin role
require_admin();

// Include database connection
require_once 'db_connection.php';

// Initialize variables
$error = '';
$success = false;

// Check if user ID is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && is_numeric($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    
    // Prevent deleting your own account
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            if (!$stmt->fetch()) {
                $error = "User not found.";
            } else {
                // Delete the user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $success = true;
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
} else {
    $error = "Invalid request.";
}

// Redirect based on result
if ($success) {
    header("Location: user_list.php?deleted=1");
    exit;
} else {
    header("Location: user_list.php?error=" . urlencode($error));
    exit;
}
?> 