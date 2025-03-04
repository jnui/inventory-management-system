<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: login.php");
    exit;
}

// Function to check if user has admin role
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Function to check admin access and redirect if not authorized
function require_admin() {
    if (!is_admin()) {
        // User is not an admin, redirect to index page
        header("Location: index.php");
        exit;
    }
}
?> 