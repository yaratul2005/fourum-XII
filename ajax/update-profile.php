<?php
require_once '../config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!is_logged_in()) {
    $response['message'] = 'You must be logged in to update your profile';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

$user_id = get_current_user_id();
$bio = sanitize_input($_POST['bio'] ?? '');

// Validation
if (strlen($bio) > 500) {
    $response['message'] = 'Bio must be less than 500 characters';
    echo json_encode($response);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
    if ($stmt->execute([$bio, $user_id])) {
        $response['success'] = true;
        $response['message'] = 'Profile updated successfully';
    } else {
        $response['message'] = 'Failed to update profile';
    }
} catch (Exception $e) {
    $response['message'] = 'Database error occurred';
    error_log('Profile update error: ' . $e->getMessage());
}

echo json_encode($response);
?>