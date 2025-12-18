<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ini_set('memory_limit', '1024M');

/**
 * Simple .env Loader
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        
        // Split by the first '=' found
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Load the environment variables
loadEnv(__DIR__ . '/.env');

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));
define('DB_NAME', getenv('DB_NAME'));

// Site configuration
define('SITE_URL', getenv('SITE_URL'));
define('SITE_NAME', getenv('SITE_NAME'));

// File upload paths
define('UPLOAD_PATH', __DIR__ . '/assets/images/'); 
define('PRODUCT_IMAGES', 'products/');
define('PROFILE_IMAGES', 'avatars/');

// Payment & Email (Pulled from .env)
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUB_KEY'));
define('STRIPE_SECRET_KEY', getenv('STRIPE_SEC_KEY'));
define('SMTP_USER', getenv('SMTP_USER'));
define('SMTP_PASS', getenv('SMTP_PASS'));

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>