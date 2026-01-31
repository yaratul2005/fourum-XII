<?php
// Start output buffering first
ob_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'greatxyz_furom');
define('DB_USER', 'greatxyz_furom');
define('DB_PASS', 'Vv123456@');

// Site configuration
define('SITE_NAME', 'Furom');
define('SITE_URL', 'https://great10.xyz');
define('ADMIN_EMAIL', 'admin@great10.xyz');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('COOKIE_DOMAIN', '.great10.xyz');

// Experience point values
define('EXP_POST', 10);
define('EXP_COMMENT', 5);
define('EXP_UPVOTE', 2);
define('EXP_DOWNVOTE', -1);
define('EXP_CATEGORY_CREATE', 25);

// Database connection with error handling
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('UTC');

// Don't flush output buffer here - let individual scripts control when to send output
// ob_end_flush(); // Removed to prevent premature header sending
?>