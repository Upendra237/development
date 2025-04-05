<?php
// includes/config.php
define('SITE_NAME', 'Sticker Shop');
define('SITE_URL', 'https://shahiupendra.com.np/order/stickers');
define('DB_PATH', __DIR__ . '/../database/stickers.db');
define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('PAYMENT_UPLOADS', UPLOAD_PATH . '/payments');
define('CUSTOM_UPLOADS', UPLOAD_PATH . '/custom-stickers');

// Ensure error reporting is off in production
error_reporting(0);
ini_set('display_errors', 0);

// Session configuration
session_start();
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);