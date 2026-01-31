<?php
// Security Functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_token() {
    return bin2hex(random_bytes(32));
}

function verify_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// User Authentication Functions
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

function get_user_data($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Experience Points Functions
function add_exp($user_id, $amount, $reason = '') {
    global $pdo;
    
    // Update user EXP
    $stmt = $pdo->prepare("UPDATE users SET exp = exp + ?, exp_reason = ? WHERE id = ?");
    $stmt->execute([$amount, $reason, $user_id]);
    
    // Log EXP transaction
    $stmt = $pdo->prepare("INSERT INTO exp_log (user_id, amount, reason, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user_id, $amount, $reason]);
}

function get_user_level($exp) {
    $levels = [
        0 => 'Newbie',
        100 => 'Beginner',
        500 => 'Member',
        1000 => 'Active Member',
        2500 => 'Veteran',
        5000 => 'Expert',
        10000 => 'Master',
        25000 => 'Legend'
    ];
    
    $current_level = 'Newbie';
    foreach ($levels as $threshold => $level) {
        if ($exp >= $threshold) {
            $current_level = $level;
        }
    }
    return $current_level;
}

// Post and Comment Functions
function create_post($user_id, $title, $content, $category = 'general') {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, category, created_at) VALUES (?, ?, ?, ?, NOW())");
    $result = $stmt->execute([$user_id, $title, $content, $category]);
    
    if ($result) {
        $post_id = $pdo->lastInsertId();
        add_exp($user_id, EXP_POST, "Created post #$post_id");
        return $post_id;
    }
    return false;
}

function create_comment($user_id, $post_id, $content) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, content, created_at) VALUES (?, ?, ?, NOW())");
    $result = $stmt->execute([$user_id, $post_id, $content]);
    
    if ($result) {
        add_exp($user_id, EXP_COMMENT, "Commented on post #$post_id");
        return $pdo->lastInsertId();
    }
    return false;
}

function vote_post($user_id, $post_id, $vote_type) {
    global $pdo;
    
    // Check if user already voted
    $stmt = $pdo->prepare("SELECT * FROM post_votes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $existing_vote = $stmt->fetch();
    
    if ($existing_vote) {
        // Update existing vote
        $stmt = $pdo->prepare("UPDATE post_votes SET vote_type = ? WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$vote_type, $user_id, $post_id]);
    } else {
        // Create new vote
        $stmt = $pdo->prepare("INSERT INTO post_votes (user_id, post_id, vote_type, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $post_id, $vote_type]);
    }
    
    // Update post score
    $score_change = ($vote_type == 'up') ? 1 : -1;
    $stmt = $pdo->prepare("UPDATE posts SET score = score + ? WHERE id = ?");
    $stmt->execute([$score_change, $post_id]);
    
    // Award EXP to post author
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post_author = $stmt->fetchColumn();
    
    if ($post_author != $user_id) {
        $exp_amount = ($vote_type == 'up') ? EXP_UPVOTE : EXP_DOWNVOTE;
        $reason = ($vote_type == 'up') ? "Post upvoted" : "Post downvoted";
        add_exp($post_author, $exp_amount, $reason);
    }
}

// Email Functions
function send_verification_email($email, $token) {
    $subject = "Verify your Furom account";
    $verification_link = SITE_URL . "/verify.php?token=" . $token;
    $message = "
    <html>
    <head>
        <title>Furom Account Verification</title>
    </head>
    <body>
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2>Welcome to Furom!</h2>
            <p>Thank you for registering. Please click the link below to verify your email address:</p>
            <p><a href='$verification_link' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verify Email</a></p>
            <p>If the button doesn't work, copy and paste this link:</p>
            <p>$verification_link</p>
            <p>This link will expire in 24 hours.</p>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . SMTP_USERNAME . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}

// Utility Functions
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function format_number($num) {
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return $num;
}
?>