<?php
// Notification System Functions

/**
 * Send notification to user
 */
function send_user_notification($user_id, $title, $message, $type = 'general', $related_id = null, $related_type = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications 
            (user_id, type, title, message, related_id, related_type, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$user_id, $type, $title, $message, $related_id, $related_type]);
    } catch (Exception $e) {
        error_log("Notification send error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send notification to all admins
 */
function send_admin_notification($title, $message, $type = 'general', $related_id = null, $related_type = null) {
    global $pdo;
    
    try {
        // Get all admin users
        $stmt = $pdo->query("SELECT id FROM users WHERE username = 'admin'");
        $admins = $stmt->fetchAll();
        
        foreach ($admins as $admin) {
            send_user_notification($admin['id'], $title, $message, $type, $related_id, $related_type);
        }
        return true;
    } catch (Exception $e) {
        error_log("Admin notification send error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notifications for user
 */
function get_unread_notifications($user_id, $limit = 5) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = 0 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get all notifications for user
 */
function get_all_notifications($user_id, $limit = 20, $offset = 0) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $limit, $offset]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Mark notification as read
 */
function mark_notification_read($notification_id, $user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notification_id, $user_id]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Mark all notifications as read for user
 */
function mark_all_notifications_read($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        return $stmt->execute([$user_id]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get notification count for user
 */
function get_notification_count($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetch()['count'];
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Delete old notifications (older than 30 days)
 */
function cleanup_old_notifications() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        return $stmt->execute();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Log KYC action for audit trail
 */
function log_kyc_action($kyc_id, $action, $performed_by = null, $notes = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO kyc_logs (kyc_id, action, performed_by, notes, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$kyc_id, $action, $performed_by, $notes]);
    } catch (Exception $e) {
        error_log("KYC log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get setting value
 */
function get_setting($key, $default = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}
?>