<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session timeout to 3 days (259200 seconds)
ini_set('session.gc_maxlifetime', 259200);
ini_set('session.cookie_lifetime', 259200);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: login.php");
    exit;
}

// Function to check if user has admin role
function require_admin() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        // User is not an admin, redirect to index page
        header("Location: index.php");
        exit;
    }
}

// Function to check if user has write access
function require_write_access() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'readonly') {
        header("Location: index.php");
        exit;
    }
}

// Function to check if user has read access
function require_read_access() {
    if (!isset($_SESSION['user_role'])) {
        header("Location: login.php");
        exit;
    }
}

// Function to check if user is read-only
function is_readonly() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'readonly';
}

// Function to check if user is admin
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
?> 