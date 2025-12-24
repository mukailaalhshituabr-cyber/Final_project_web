<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set memory limit
ini_set('memory_limit', '1024M');

// Security headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');

/**
 * Environment Loader
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        // Create default .env if it doesn't exist
        $defaultContent = <<<ENV
# Database Configuration
DB_HOST=localhost
DB_USER=mukaila_shittu
DB_PASS=
DB_NAME=webtech_2025A_shittu

# Site Configuration
SITE_URL=http://mukaila.shittu.socialngn.com/Final_project_web
SITE_NAME=Clothing Marketplace

# Application Settings
DEBUG_MODE=true
TIMEZONE=Africa/Lagos
MAX_UPLOAD_SIZE=10485760
ENV;
        file_put_contents($path, $defaultContent);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip comments
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Load environment variables
loadEnv(__DIR__ . '/.env');

// =============================================
// DATABASE CONFIGURATION
// =============================================
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'mukaila_shittu');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'webtech_2025A_shittu');

// =============================================
// SITE CONFIGURATION
// =============================================
// Auto-detect SITE_URL if not set in .env
$siteUrl = getenv('SITE_URL');
if (!$siteUrl) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $siteUrl = $protocol . '://' . $host . dirname($_SERVER['SCRIPT_NAME']);
}
define('SITE_URL', rtrim($siteUrl, '/'));

define('SITE_NAME', getenv('SITE_NAME') ?: 'Clothing Marketplace');

// =============================================
// FILE PATHS
// =============================================
define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
define('ASSETS_PATH', ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR);
define('UPLOAD_PATH', ASSETS_PATH . 'images' . DIRECTORY_SEPARATOR);
define('PRODUCT_IMAGES_PATH', UPLOAD_PATH . 'products' . DIRECTORY_SEPARATOR);
define('PROFILE_IMAGES_PATH', UPLOAD_PATH . 'avatars' . DIRECTORY_SEPARATOR);

// URLs for HTML
define('ASSETS_URL', SITE_URL . '/assets/');
define('CSS_URL', ASSETS_URL . 'css/');
define('JS_URL', ASSETS_URL . 'js/');
define('IMAGES_URL', ASSETS_URL . 'images/');

// =============================================
// APPLICATION CONSTANTS (From your database)
// =============================================
// User types
define('USER_CUSTOMER', 'customer');
define('USER_TAILOR', 'tailor');
define('USER_ADMIN', 'admin');

// Product statuses
define('PRODUCT_DRAFT', 'draft');
define('PRODUCT_ACTIVE', 'active');
define('PRODUCT_INACTIVE', 'inactive');
define('PRODUCT_OUT_OF_STOCK', 'out_of_stock');

// Order statuses
define('ORDER_PENDING', 'pending');
define('ORDER_CONFIRMED', 'confirmed');
define('ORDER_PROCESSING', 'processing');
define('ORDER_READY', 'ready');
define('ORDER_SHIPPED', 'shipped');
define('ORDER_DELIVERED', 'delivered');
define('ORDER_CANCELLED', 'cancelled');
define('ORDER_REFUNDED', 'refunded');

// Payment statuses
define('PAYMENT_PENDING', 'pending');
define('PAYMENT_PAID', 'paid');
define('PAYMENT_FAILED', 'failed');
define('PAYMENT_REFUNDED', 'refunded');

// =============================================
// ERROR REPORTING
// =============================================
$debugMode = filter_var(getenv('DEBUG_MODE'), FILTER_VALIDATE_BOOLEAN);
if ($debugMode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . 'logs/error.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
$timezone = getenv('TIMEZONE') ?: 'Africa/Lagos';
date_default_timezone_set($timezone);

// =============================================
// HELPER FUNCTIONS
// =============================================
/**
 * Get user-friendly category name
 */
function get_category_name($category_slug) {
    $categories = [
        'traditional-wear' => 'Traditional Wear',
        'modern-fashion' => 'Modern Fashion',
        'formal' => 'Formal Wear',
        'custom' => 'Custom Designs'
    ];
    return $categories[$category_slug] ?? ucwords(str_replace('-', ' ', $category_slug));
}

/**
 * Format price with currency
 */
function format_price($price) {
    return 'CFA ' . number_format($price, 0, '.', ',');
}

/**
 * Get user type label
 */
function get_user_type_label($type) {
    $labels = [
        'customer' => 'Customer',
        'tailor' => 'Tailor',
        'admin' => 'Administrator'
    ];
    return $labels[$type] ?? 'User';
}

/**
 * Get order status badge class
 */
function get_order_status_badge($status) {
    $badges = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'processing' => 'primary',
        'ready' => 'success',
        'shipped' => 'success',
        'delivered' => 'success',
        'cancelled' => 'danger',
        'refunded' => 'secondary'
    ];
    return $badges[$status] ?? 'secondary';
}

/**
 * Get payment status badge class
 */
function get_payment_status_badge($status) {
    $badges = [
        'pending' => 'warning',
        'paid' => 'success',
        'failed' => 'danger',
        'refunded' => 'info'
    ];
    return $badges[$status] ?? 'secondary';
}

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize_input($value);
        }
        return $data;
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect with message
 */
function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header('Location: ' . $url);
    exit();
}

// =============================================
// SESSION SECURITY
// =============================================
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// =============================================
// CREATE REQUIRED DIRECTORIES
// =============================================
$required_dirs = [
    UPLOAD_PATH,
    PRODUCT_IMAGES_PATH,
    PROFILE_IMAGES_PATH,
    ROOT_PATH . 'logs/',
    ROOT_PATH . 'cache/',
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// =============================================
// GLOBAL CATEGORIES
// =============================================
$GLOBALS['categories'] = [
    'traditional-wear' => ['name' => 'Traditional Wear', 'icon' => 'bi-rainbow'],
    'modern-fashion' => ['name' => 'Modern Fashion', 'icon' => 'bi-lightning'],
    'formal' => ['name' => 'Formal Wear', 'icon' => 'bi-suit-spade'],
    'custom' => ['name' => 'Custom Designs', 'icon' => 'bi-brush']
];

// Output buffering for better performance
if (!ob_get_level()) {
    ob_start();
}
?>