<?php
require_once '../../config.php';
require_once '../../includes/functions/auth_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create auth functions instance
$auth = new AuthFunctions();

// Store some data for feedback
$username = $_SESSION['username'] ?? 'User';
$redirect = $_GET['redirect'] ?? 'index.php';

// Clear all session data
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Clear any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Redirect to login page with logout message
$_SESSION['success_message'] = "You have been successfully logged out. Goodbye, $username!";
header('Location: ' . SITE_URL . '/pages/auth/login.php?logout=success');
exit();
?>