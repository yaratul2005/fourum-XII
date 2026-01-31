<?php
// Simplified Configuration for Basic Hosting
// Rename this to config.php if you encounter issues

// Basic error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration - UPDATE THESE VALUES
define('DB_HOST', 'localhost');
define('DB_USER', 'your_database_username');
define('DB_PASS', 'your_database_password');
define('DB_NAME', 'your_database_name');

// Site Configuration
define('SITE_URL', 'https://great10.xyz');
define('SITE_NAME', 'Furom - Futuristic Forum');
define('ADMIN_EMAIL', 'webmaster@great10.xyz');

// Security Settings
define('SECRET_KEY', 'change_this_to_a_random_string');
define('SESSION_TIMEOUT', 3600);

// Experience Points Configuration
define('EXP_POST', 10);
define('EXP_COMMENT', 5);
define('EXP_UPVOTE', 2);
define('EXP_DOWNVOTE', -1);

// Simple session start
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection with basic error handling
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // For debugging - remove in production
    die("Database Connection Failed: " . $e->getMessage() . "<br>
         Please check your database credentials in config.php<br>
         Host: " . DB_HOST . "<br>
         Database: " . DB_NAME . "<br>
         User: " . DB_USER);
}

// Simple autoloader for functions
if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
} else {
    // Basic functions if includes don't exist yet
    function sanitize_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }
    
    function redirect_if_not_logged_in() {
        if (!is_logged_in()) {
            header('Location: login.php');
            exit();
        }
    }
    
    function get_current_user_id() {
        return $_SESSION['user_id'] ?? null;
    }
}
?>