<?php
/**
 * LOGOUT PAGE
 * Path: pages/auth/logout.php
 */

// 1. Load configuration and functions
require_once '../../config.php';

// Check if file exists before requiring to prevent further fatal errors
$auth_path = '../../includes/functions/auth_functions.php';
if (file_exists($auth_path)) {
    require_once $auth_path;
} else {
    // Fallback if path is different in your structure
    require_once '../../includes/classes/AuthFunctions.php';
}

// 2. Start session to access session data before destroying it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Handle the Class Instance
// We use a class_exists check to prevent the "Class not found" fatal error
if (class_exists('AuthFunctions')) {
    $auth = new AuthFunctions();
    // If your AuthFunctions class has a custom logout method, call it here
    // $auth->logout(); 
}

// 4. Capture username for the goodbye message before clearing
$username = $_SESSION['username'] ?? 'User';

// 5. Securely Clear all session data
$_SESSION = array();

// 6. Delete the session cookie from the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 7. Destroy the session on the server
session_destroy();

// 8. Clear any output buffers to prevent "Headers already sent" errors
while (ob_get_level()) {
    ob_end_clean();
}

/**
 * 9. Redirect
 * Note: Since session_destroy() was called, we use a URL parameter 
 * to show the message on the login page instead of a $_SESSION variable.
 */
$login_url = SITE_URL . '/pages/auth/login.php?logout=success&user=' . urlencode($username);
header('Location: ' . $login_url);
exit();