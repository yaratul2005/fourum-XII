<?php
// AJAX endpoint to mark notification as read
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
    $input = json_decode(file_get_contents('php://input'), true);
    $notification_id = (int)($input['notification_id'] ?? 0);
    
    if ($notification_id <= 0) {
        throw new Exception('Invalid notification ID');
    }
    
    $user_id = get_current_user_id();
    
    // Verify notification belongs to user
    $stmt = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $user_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Notification not found or unauthorized');
    }
    
    // Mark as read
    if (mark_notification_read($notification_id, $user_id)) {
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } else {
        throw new Exception('Failed to mark notification as read');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>