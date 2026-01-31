<?php
require_once '../../config.php';
require_once '../../includes/functions.php';

// Check admin access
if (!is_logged_in() || get_user_data(get_current_user_id())['username'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    case 'ban_user':
        $user_id = intval($_POST['user_id'] ?? 0);
        $reason = sanitize_input($_POST['reason'] ?? 'Administrative action');
        
        if ($user_id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET banned = 1, banned_reason = ?, banned_at = NOW() WHERE id = ?");
                $stmt->execute([$reason, $user_id]);
                echo json_encode(['success' => true, 'message' => 'User banned successfully']);
            } catch(Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error banning user']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        }
        break;
        
    case 'unban_user':
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if ($user_id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET banned = 0, banned_reason = NULL, banned_at = NULL WHERE id = ?");
                $stmt->execute([$user_id]);
                echo json_encode(['success' => true, 'message' => 'User unbanned successfully']);
            } catch(Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error unbanning user']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        }
        break;
        
    case 'delete_user':
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if ($user_id > 0) {
            try {
                // Delete user and all their content
                $pdo->beginTransaction();
                
                // Delete user's posts
                $stmt = $pdo->prepare("DELETE FROM posts WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Delete user's comments
                $stmt = $pdo->prepare("DELETE FROM comments WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Delete user's votes
                $stmt = $pdo->prepare("DELETE FROM votes WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Delete user's reports
                $stmt = $pdo->prepare("DELETE FROM reports WHERE user_id = ? OR target_user_id = ?");
                $stmt->execute([$user_id, $user_id]);
                
                // Finally delete the user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'User and all content deleted successfully']);
            } catch(Exception $e) {
                $pdo->rollback();
                echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        }
        break;
        
    case 'delete_post':
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if ($post_id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE posts SET status = 'deleted' WHERE id = ?");
                $stmt->execute([$post_id]);
                echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
            } catch(Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error deleting post']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        }
        break;
        
    case 'restore_post':
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if ($post_id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE posts SET status = 'active' WHERE id = ?");
                $stmt->execute([$post_id]);
                echo json_encode(['success' => true, 'message' => 'Post restored successfully']);
            } catch(Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error restoring post']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        }
        break;
        
    case 'get_stats':
        try {
            $stats = [
                'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                'total_posts' => $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'active'")->fetchColumn(),
                'total_comments' => $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'active'")->fetchColumn(),
                'pending_reports' => $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn(),
                'banned_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE banned = 1")->fetchColumn(),
                'active_today' => $pdo->query("SELECT COUNT(*) FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 1 DAY)")->fetchColumn()
            ];
            echo json_encode(['success' => true, 'stats' => $stats]);
        } catch(Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching statistics']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>