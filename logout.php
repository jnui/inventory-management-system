<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the session cookie name and parameters before destroying the session
$session_name = session_name();
$session_cookie_params = session_get_cookie_params();

// Unset all session variables
$_SESSION = array();

// Delete the session cookie if it exists
if (isset($_COOKIE[$session_name])) {
    setcookie(
        $session_name,
        '',
        [
            'expires' => time() - 42000,
            'path' => $session_cookie_params['path'],
            'domain' => $session_cookie_params['domain'],
            'secure' => $session_cookie_params['secure'],
            'httponly' => $session_cookie_params['httponly'],
            'samesite' => $session_cookie_params['samesite'] ?? 'Lax'
        ]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
?> 