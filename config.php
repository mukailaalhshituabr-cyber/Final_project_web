<?php

//done// Start session at the very beginning
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
 * .env Loader for your specific setup
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        // Create default .env for your hosting
        $defaultContent = <<<ENV
# Database Configuration
DB_HOST=localhost
DB_USER=mukaila_shittu
DB_PASS=your_mysql_password_here
DB_NAME=webtech_2025A_shittu

# Site Configuration
SITE_URL=https://mukaila.shittu.socialngn.com/Final_project_web
SITE_NAME=Global Clothing Marketplace

# Timezone (Africa/Lagos for Nigeria)
TIMEZONE=Africa/Lagos

# Debug Mode (true for development, false for production)
DEBUG_MODE=true

# File Uploads
MAX_UPLOAD_SIZE=10485760  # 10MB
ENV;
        
        file_put_contents($path, $defaultContent);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            
            putenv("$key=$value");
            $_ENV[$key] = $value;
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
// =============================================
// SITE CONFIGURATION
// =============================================
$siteUrl = getenv('SITE_URL');
if (!$siteUrl) {
    // Use a more reliable method to detect the base URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'mukaila.shittu.socialngn.com';
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    
    // Remove /Final_project_web if it's already in the path
    $basePath = str_replace('/Final_project_web', '', $scriptPath);
    $siteUrl = $protocol . '://' . $host . $basePath . '/Final_project_web';
}
define('SITE_URL', rtrim($siteUrl, '/'));
define('SITE_URL', rtrim($siteUrl, '/'));

define('SITE_NAME', getenv('SITE_NAME') ?: 'Global Clothing Marketplace');

// =============================================
// FILE PATHS
// =============================================
define('ROOT_PATH', __DIR__ . '/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');
define('UPLOAD_PATH', ROOT_PATH . 'assets/images/');
define('PRODUCT_IMAGES_PATH', UPLOAD_PATH . 'products/');
define('PROFILE_IMAGES_PATH', UPLOAD_PATH . 'avatars/');

// Relative paths for HTML
define('ASSETS_URL', SITE_URL . '/assets/');
define('CSS_URL', ASSETS_URL . 'css/');
define('JS_URL', ASSETS_URL . 'js/');
define('IMAGES_URL', ASSETS_URL . 'images/');

// =============================================
// APPLICATION CONSTANTS (MATCHING YOUR DB EXACTLY)
// =============================================
// User types from your users table
define('USER_CUSTOMER', 'customer');
define('USER_TAILOR', 'tailor');
define('USER_ADMIN', 'admin');

// User status from your users table
define('USER_STATUS_ACTIVE', 'active');
define('USER_STATUS_INACTIVE', 'inactive');
define('USER_STATUS_SUSPENDED', 'suspended');

// Product statuses from your products table
define('PRODUCT_DRAFT', 'draft');
define('PRODUCT_ACTIVE', 'active');
define('PRODUCT_INACTIVE', 'inactive');
define('PRODUCT_OUT_OF_STOCK', 'out_of_stock');

// Order statuses from your orders table
define('ORDER_PENDING', 'pending');
define('ORDER_CONFIRMED', 'confirmed');
define('ORDER_PROCESSING', 'processing');
define('ORDER_READY', 'ready');
define('ORDER_SHIPPED', 'shipped');
define('ORDER_DELIVERED', 'delivered');
define('ORDER_CANCELLED', 'cancelled');
define('ORDER_REFUNDED', 'refunded');

// Payment statuses from your orders table
define('PAYMENT_PENDING', 'pending');
define('PAYMENT_PAID', 'paid');
define('PAYMENT_FAILED', 'failed');
define('PAYMENT_REFUNDED', 'refunded');

// Order item statuses
define('ORDER_ITEM_PENDING', 'pending');
define('ORDER_ITEM_IN_PRODUCTION', 'in_production');
define('ORDER_ITEM_COMPLETED', 'completed');
define('ORDER_ITEM_CANCELLED', 'cancelled');

// Review statuses
define('REVIEW_PENDING', 'pending');
define('REVIEW_APPROVED', 'approved');
define('REVIEW_REJECTED', 'rejected');

// =============================================
// ERROR REPORTING
// =============================================
$debugMode = getenv('DEBUG_MODE') === 'true' || getenv('DEBUG_MODE') === '1';
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
 * Get user-friendly category names
 */
function get_category_name($category_slug) {
    $categories = [
        'traditional-wear' => 'Traditional Wear',
        'modern-fashion' => 'Modern Fashion',
        'formal' => 'Formal Wear',
        'custom' => 'Custom Designs'
    ];
    return $categories[$category_slug] ?? ucfirst(str_replace('-', ' ', $category_slug));
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
        'refunded' => 'secondary'
    ];
    return $badges[$status] ?? 'secondary';
}

// =============================================
// SESSION SECURITY
// =============================================
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// =============================================
// AUTO-CREATE REQUIRED DIRECTORIES
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

// Output buffering for better performance
if (!ob_get_level()) {
    ob_start();
}
?>



<?php
/*// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set memory limit
ini_set('memory_limit', '1024M');

// Security headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');


function loadEnv($path) {
    if (!file_exists($path)) {
        // Create default .env for your hosting
        $defaultContent = <<<ENV
# Database Configuration for mukaila.shittu.socialngn.com
DB_HOST=localhost
DB_USER=mukaila_shittu
DB_PASS=your_mysql_password_here
DB_NAME=webtech_2025A_shittu

# Site Configuration
SITE_URL=https://mukaila.shittu.socialngn.com/Final_project_web
SITE_NAME=Clothing Marketplace

# Timezone (Africa/Lagos for Nigeria)
TIMEZONE=Africa/Lagos

# Debug Mode (true for development, false for production)
DEBUG_MODE=true

# File Uploads
MAX_UPLOAD_SIZE=10485760  # 10MB
ENV;
        
        file_put_contents($path, $defaultContent);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Load environment variables
loadEnv(__DIR__ . '/.env');

// =============================================
// DATABASE CONFIGURATION (YOUR DATABASE)
// =============================================
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'mukaila_shittu');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'webtech_2025A_shittu');

// =============================================
// SITE CONFIGURATION
// =============================================
$siteUrl = getenv('SITE_URL');
if (!$siteUrl) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'mukaila.shittu.socialngn.com';
    $siteUrl = $protocol . '://' . $host . '/Final_project_web';
}
define('SITE_URL', rtrim($siteUrl, '/'));

define('SITE_NAME', getenv('SITE_NAME') ?: 'Clothing Marketplace');

// =============================================
// FILE PATHS
// =============================================
define('ROOT_PATH', __DIR__ . '/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');
define('UPLOAD_PATH', ROOT_PATH . 'assets/images/');
define('PRODUCT_IMAGES_PATH', UPLOAD_PATH . 'products/');
define('PROFILE_IMAGES_PATH', UPLOAD_PATH . 'avatars/');

// Relative paths for HTML
define('ASSETS_URL', SITE_URL . '/assets/');
define('CSS_URL', ASSETS_URL . 'css/');
define('JS_URL', ASSETS_URL . 'js/');
define('IMAGES_URL', ASSETS_URL . 'images/');

// =============================================
// APPLICATION CONSTANTS (MATCHING YOUR DB)
// =============================================
// User types from your users table
define('USER_CUSTOMER', 'customer');
define('USER_TAILOR', 'tailor');
define('USER_ADMIN', 'admin');

// Product statuses from your products table
define('PRODUCT_DRAFT', 'draft');
define('PRODUCT_ACTIVE', 'active');
define('PRODUCT_INACTIVE', 'inactive');
define('PRODUCT_OUT_OF_STOCK', 'out_of_stock');

// Order statuses from your orders table
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
$debugMode = getenv('DEBUG_MODE') === 'true' || getenv('DEBUG_MODE') === '1';
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


function get_category_name($category_slug) {
    $categories = [
        'traditional-wear' => 'Traditional Wear',
        'modern-fashion' => 'Modern Fashion',
        'traditional' => 'Traditional Clothing',
        'modern' => 'Modern Fashion',
        'formal' => 'Formal Wear',
        'custom' => 'Custom Designs'
    ];
    return $categories[$category_slug] ?? ucfirst(str_replace('-', ' ', $category_slug));
}


function format_price($price) {
    return 'CFA ' . number_format($price, 0, '.', ',');
}


function get_user_type_label($type) {
    $labels = [
        'customer' => 'Customer',
        'tailor' => 'Tailor',
        'admin' => 'Administrator'
    ];
    return $labels[$type] ?? 'User';
}


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

// =============================================
// SESSION SECURITY
// =============================================
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// =============================================
// AUTO-CREATE REQUIRED DIRECTORIES
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
// GLOBAL CATEGORIES (from your DB)
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



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ini_set('memory_limit', '1024M');


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
*/