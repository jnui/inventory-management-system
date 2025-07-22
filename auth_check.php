<?php
// Set session cookie parameters BEFORE starting the session (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    $lifetime = 259200; // 3 days
    // Set GC max lifetime BEFORE session start
    ini_set('session.gc_maxlifetime', $lifetime);

    // Set cookie params before session start
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

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