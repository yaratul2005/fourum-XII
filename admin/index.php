<?php
// Admin Panel Router - Centralized Control System
session_start();

// Include core configuration
require_once '../config.php';
require_once '../includes/functions.php';

// Check admin authentication
if (!is_admin()) {
    header('Location: ../login.php');
    exit();
}

// Route mapping
$routes = [
    '' => 'dashboard.php',
    'dashboard' => 'dashboard.php',
    'users' => 'users.php',
    'posts' => 'posts.php',
    'categories' => 'categories.php',
    'reports' => 'reports.php',
    'settings' => 'settings.php',
    'smtp' => 'smtp-settings.php',
    'google-auth' => 'google-auth-settings.php',
    'backup' => 'backup.php',
    'kyc' => 'kyc-management.php',
    'email-test' => 'email-test-tool.php'
];

// Get requested route
$route = isset($_GET['page']) ? $_GET['page'] : '';

// Default to dashboard if no route specified
if (empty($route)) {
    $route = 'dashboard';
}

// Check if route exists
if (isset($routes[$route])) {
    $target_file = $routes[$route];
    if (file_exists($target_file)) {
        include $target_file;
        exit();
    }
}

// Fallback to dashboard if route not found
include 'dashboard.php';
?>