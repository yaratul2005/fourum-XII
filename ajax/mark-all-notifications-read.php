<?php
// AJAX endpoint to mark all notifications as read
require_once '../config.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php';

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    $user_id = get_current_user_id();
    
    if (mark_all_notifications_read($user_id)) {
        echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
    } else {
        throw new Exception('Failed to mark notifications as read');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>