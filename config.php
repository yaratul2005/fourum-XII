<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'furom_db');

// Site Configuration
define('SITE_URL', 'https://great10.xyz');
define('SITE_NAME', 'Furom - Futuristic Forum');
define('ADMIN_EMAIL', 'admin@great10.xyz');

// Security Settings
define('SECRET_KEY', 'your_very_secure_secret_key_here');
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

// Initialize session
session_start();

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Autoload functions
require_once 'includes/functions.php';
?>