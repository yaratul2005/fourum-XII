<?php
require_once '../config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!is_logged_in()) {
    $response['message'] = 'You must be logged in to comment';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$content = sanitize_input($_POST['content'] ?? '');

// Validation
if (!$post_id) {
    $response['message'] = 'Invalid post ID';
    echo json_encode($response);
    exit();
}

if (empty($content)) {
    $response['message'] = 'Comment content is required';
    echo json_encode($response);
    exit();
}

if (strlen($content) < 1) {
    $response['message'] = 'Comment must be at least 1 character';
    echo json_encode($response);
    exit();
}

if (strlen($content) > 10000) {
    $response['message'] = 'Comment too long (maximum 10000 characters)';
    echo json_encode($response);
    exit();
}

// Verify post exists
$stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND status = 'active'");
$stmt->execute([$post_id]);
if (!$stmt->fetch()) {
    $response['message'] = 'Post not found';
    echo json_encode($response);
    exit();
}

try {
    $comment_id = create_comment(get_current_user_id(), $post_id, $content);
    
    if ($comment_id) {
        $response['success'] = true;
        $response['message'] = 'Comment posted successfully';
        $response['comment_id'] = $comment_id;
    } else {
        $response['message'] = 'Failed to create comment';
    }
    
} catch (Exception $e) {
    $response['message'] = 'Database error occurred';
    error_log('Comment creation error: ' . $e->getMessage());
}

echo json_encode($response);
?>