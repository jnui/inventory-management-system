<?php
// Set session cookie parameters BEFORE starting the session (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    // 3 days in seconds: 259200
    session_set_cookie_params([
        'lifetime' => 259200,      // 3 days in seconds
        'path' => '/',             // Cookie available for entire domain
        'domain' => '',            // Current domain only
        'secure' => false,         // Allow both HTTP and HTTPS (change to true if site is HTTPS only)
        'httponly' => true,        // Cookie not accessible via JavaScript
        'samesite' => 'Lax'        // Protect against CSRF
    ]);
    session_start();
}

// Ensure PHP's garbage collection is set correctly
ini_set('session.gc_maxlifetime', 259200); // 3 days to match cookie

// Activity timeout - if user has been inactive for too long, log them out
// Only activate this if you want to log out inactive users even if their session is still valid
// Set to 0 to disable timeout check
$inactivity_timeout = 0; // in seconds, 0 = disabled

if ($inactivity_timeout > 0 && isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    if ($inactive_time > $inactivity_timeout) {
        // Session has expired due to inactivity
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit;
    }
}

// Update last activity time
if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
}

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