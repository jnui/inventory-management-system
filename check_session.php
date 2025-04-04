<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Session Debug Information</h1>";

// Display all session variables
echo "<h2>Session Variables:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if user is logged in
echo "<h2>Login Status:</h2>";
if (isset($_SESSION['user_id'])) {
    echo "User is logged in<br>";
    echo "User ID: " . htmlspecialchars($_SESSION['user_id']) . "<br>";
    echo "User Name: " . htmlspecialchars($_SESSION['user_name']) . "<br>";
    echo "User Initials: " . htmlspecialchars($_SESSION['user_initials']) . "<br>";
    echo "User Role: " . htmlspecialchars($_SESSION['user_role']) . "<br>";
} else {
    echo "User is not logged in";
}

// Check admin access
echo "<h2>Admin Access Check:</h2>";
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    echo "User has admin privileges<br>";
} else {
    echo "User does NOT have admin privileges<br>";
}

// Display current script path
echo "<h2>Current Script:</h2>";
echo "Script path: " . htmlspecialchars($_SERVER['PHP_SELF']) . "<br>";

// Add a link to go back
echo "<p><a href='index.php'>Back to Home</a></p>";
?> 