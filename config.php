<?php
session_start();
ini_set('memory_limit', '1024M');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'mukaila.shittu');
define('DB_PASS', 'Adf=Tdd3&W');
define('DB_NAME', 'webtech_2025A_mukaila_shittu');

// Site configuration
define('SITE_URL', 'http://169.239.251.102:341/~mukaila.shittu/Final_project_web/');
define('SITE_NAME', 'Global Clothing Marketplace');

// File upload paths
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/clothing-marketplaces/assets/uploads/');
define('PRODUCT_IMAGES', 'products/');
define('PROFILE_IMAGES', 'profile/');

// Payment configuration (you'll need to get actual keys)
define('STRIPE_PUBLISHABLE_KEY', 'your_stripe_publishable_key');
define('STRIPE_SECRET_KEY', 'your_stripe_secret_key');

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>