<?php
// Enable output buffering to prevent header issues
ob_start();

// Database Configuration - UPDATE THESE VALUES WITH YOUR CPANEL CREDENTIALS
define('DB_HOST', 'localhost');
define('DB_USER', 'greatxyz_admin'); // Replace with your cPanel username + _admin
define('DB_PASS', 'c(sYbk1;hlCwRQo!'); // Replace with your actual database password
define('DB_NAME', 'greatxyz_admin'); // Replace with your actual database name

// Site Configuration
define('SITE_URL', 'https://great10.xyz');
define('SITE_NAME', 'Furom - Futuristic Forum');
define('ADMIN_EMAIL', 'admin@great10.xyz');

// Security Settings
define('SECRET_KEY', 'furom_secure_key_' . date('Ymd')); // More secure secret key
define('SESSION_TIMEOUT', 3600); // 1 hour

// Experience Points Configuration
define('EXP_POST', 10);
define('EXP_COMMENT', 5);
define('EXP_UPVOTE', 2);
define('EXP_DOWNVOTE', -1);

// Email Configuration
define('SMTP_HOST', 'mail.great10.xyz');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@great10.xyz');
define('SMTP_PASSWORD', 'your_email_password');

// Initialize session with error prevention
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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
    // Log error instead of displaying it publicly
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection error. Please check configuration.");
}

// Flush output buffer
ob_end_flush();
?>