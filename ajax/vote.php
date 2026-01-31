<?php
require_once '../config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!is_logged_in()) {
    $response['message'] = 'You must be logged in to vote';
    echo json_encode($response);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['item_id']) || !isset($data['vote_type']) || !isset($data['item_type'])) {
    $response['message'] = 'Invalid request data';
    echo json_encode($response);
    exit();
}

$item_id = (int)$data['item_id'];
$vote_type = $data['vote_type'];
$item_type = $data['item_type'];
$user_id = get_current_user_id();

// Validate vote type
if (!in_array($vote_type, ['up', 'down'])) {
    $response['message'] = 'Invalid vote type';
    echo json_encode($response);
    exit();
}

// Validate item type
if (!in_array($item_type, ['post', 'comment'])) {
    $response['message'] = 'Invalid item type';
    echo json_encode($response);
    exit();
}

$table = $item_type . '_votes';
$item_table = $item_type . 's';
$score_column = $item_type === 'post' ? 'score' : 'score';

try {
    // Check if user already voted on this item
    $stmt = $pdo->prepare("SELECT id, vote_type FROM $table WHERE user_id = ? AND {$item_type}_id = ?");
    $stmt->execute([$user_id, $item_id]);
    $existing_vote = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_vote) {
        // User has already voted
        if ($existing_vote['vote_type'] === $vote_type) {
            // Remove vote (toggle off)
            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->execute([$existing_vote['id']]);
            
            // Update item score
            $score_change = $vote_type === 'up' ? -1 : 1;
            $stmt = $pdo->prepare("UPDATE $item_table SET $score_column = $score_column + ? WHERE id = ?");
            $stmt->execute([$score_change, $item_id]);
            
            $response['message'] = 'Vote removed';
        } else {
            // Change vote
            $stmt = $pdo->prepare("UPDATE $table SET vote_type = ? WHERE id = ?");
            $stmt->execute([$vote_type, $existing_vote['id']]);
            
            // Update item score
            $score_change = $vote_type === 'up' ? 2 : -2; // Change from down to up = +2, up to down = -2
            $stmt = $pdo->prepare("UPDATE $item_table SET $score_column = $score_column + ? WHERE id = ?");
            $stmt->execute([$score_change, $item_id]);
            
            $response['message'] = 'Vote changed';
        }
    } else {
        // New vote
        $stmt = $pdo->prepare("INSERT INTO $table (user_id, {$item_type}_id, vote_type, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $item_id, $vote_type]);
        
        // Update item score
        $score_change = $vote_type === 'up' ? 1 : -1;
        $stmt = $pdo->prepare("UPDATE $item_table SET $score_column = $score_column + ? WHERE id = ?");
        $stmt->execute([$score_change, $item_id]);
        
        // Award EXP to item author (except for self-votes)
        if ($item_type === 'post') {
            $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
            $stmt->execute([$item_id]);
            $author_id = $stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
            $stmt->execute([$item_id]);
            $author_id = $stmt->fetchColumn();
        }
        
        if ($author_id && $author_id != $user_id) {
            $exp_amount = $vote_type === 'up' ? EXP_UPVOTE : EXP_DOWNVOTE;
            $reason = $vote_type === 'up' ? ucfirst($item_type) . ' upvoted' : ucfirst($item_type) . ' downvoted';
            add_exp($author_id, $exp_amount, $reason);
        }
        
        $response['message'] = 'Vote recorded';
    }
    
    // Get new score
    $stmt = $pdo->prepare("SELECT $score_column FROM $item_table WHERE id = ?");
    $stmt->execute([$item_id]);
    $new_score = $stmt->fetchColumn();
    
    // Get user's current vote
    $stmt = $pdo->prepare("SELECT vote_type FROM $table WHERE user_id = ? AND {$item_type}_id = ?");
    $stmt->execute([$user_id, $item_id]);
    $user_vote = $stmt->fetchColumn();
    
    $response['success'] = true;
    $response['new_score'] = (int)$new_score;
    $response['user_vote'] = $user_vote;
    
} catch (Exception $e) {
    $response['message'] = 'Database error occurred';
    error_log('Vote error: ' . $e->getMessage());
}

echo json_encode($response);
?>