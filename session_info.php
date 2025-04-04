<?php
// Include auth check to ensure the user is logged in
require_once 'auth_check.php';

// Only allow admin access
require_admin();

// Show current session information
echo '<h1>Session Information</h1>';

// Show session data
echo '<h2>Session Data:</h2>';
echo '<pre>';
print_r($_SESSION);
echo '</pre>';

// Show session configuration
echo '<h2>Session Configuration:</h2>';
echo '<table border="1" cellpadding="5">';
echo '<tr><th>Setting</th><th>Value</th></tr>';
echo '<tr><td>session.gc_maxlifetime</td><td>' . ini_get('session.gc_maxlifetime') . ' seconds</td></tr>';
echo '<tr><td>session.cookie_lifetime</td><td>' . ini_get('session.cookie_lifetime') . ' seconds</td></tr>';
echo '<tr><td>session.cookie_path</td><td>' . ini_get('session.cookie_path') . '</td></tr>';
echo '<tr><td>session.cookie_domain</td><td>' . ini_get('session.cookie_domain') . '</td></tr>';
echo '<tr><td>session.cookie_secure</td><td>' . (ini_get('session.cookie_secure') ? 'Yes' : 'No') . '</td></tr>';
echo '<tr><td>session.cookie_httponly</td><td>' . (ini_get('session.cookie_httponly') ? 'Yes' : 'No') . '</td></tr>';
echo '<tr><td>session.cookie_samesite</td><td>' . ini_get('session.cookie_samesite') . '</td></tr>';
echo '<tr><td>session.name</td><td>' . ini_get('session.name') . '</td></tr>';
echo '<tr><td>session.save_handler</td><td>' . ini_get('session.save_handler') . '</td></tr>';
echo '<tr><td>session.save_path</td><td>' . ini_get('session.save_path') . '</td></tr>';
echo '</table>';

// Show time information
echo '<h2>Time Information:</h2>';
echo '<table border="1" cellpadding="5">';
echo '<tr><th>Item</th><th>Value</th></tr>';
echo '<tr><td>Current Server Time</td><td>' . date('Y-m-d H:i:s') . '</td></tr>';

if (isset($_SESSION['last_activity'])) {
    echo '<tr><td>Last Activity Time</td><td>' . date('Y-m-d H:i:s', $_SESSION['last_activity']) . '</td></tr>';
    $inactive_time = time() - $_SESSION['last_activity'];
    echo '<tr><td>Inactive Time</td><td>' . $inactive_time . ' seconds</td></tr>';
}

echo '</table>';

// Show cookie information
echo '<h2>Session Cookie Information:</h2>';
if (isset($_COOKIE[session_name()])) {
    echo '<p>Session cookie exists: ' . htmlspecialchars($_COOKIE[session_name()]) . '</p>';
} else {
    echo '<p>Session cookie does not exist!</p>';
}

// Show all cookies
echo '<h3>All Cookies:</h3>';
echo '<pre>';
print_r($_COOKIE);
echo '</pre>';

// Add a link back to the index page
echo '<p><a href="index.php">Back to Home</a></p>';
?> 