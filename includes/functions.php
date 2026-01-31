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
    global $pdo;
    
    try {
        // Get SMTP settings from database or config
        $smtp_settings = get_smtp_settings();
        
        $subject = "Verify your Furom account";
        $verification_link = SITE_URL . "/verify.php?token=" . $token;
        
        $message = "
        <html>
        <head>
            <title>Furom Account Verification</title>
            <style>
                body { font-family: Arial, sans-serif; background: #0a0a1a; color: #f0f0f0; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #121225; border-radius: 10px; padding: 30px; }
                .header { text-align: center; margin-bottom: 30px; }
                .header h1 { color: #00f5ff; margin: 0; }
                .content { line-height: 1.6; }
                .button { display: inline-block; background: linear-gradient(45deg, #00f5ff, #ff00ff); color: #0a0a1a; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: bold; margin: 20px 0; }
                .footer { margin-top: 30px; font-size: 0.9em; color: #888; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1><i class='fas fa-robot'></i> FUROM</h1>
                    <h2>Welcome to Our Community!</h2>
                </div>
                <div class='content'>
                    <p>Thank you for joining Furom! We're excited to have you as part of our futuristic community.</p>
                    <p>To complete your registration and start earning EXP, please verify your email address by clicking the button below:</p>
                    <center><a href='$verification_link' class='button'>Verify My Email</a></center>
                    <p>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; background: rgba(0,245,255,0.1); padding: 10px; border-radius: 5px;'><a href='$verification_link' style='color: #00f5ff;'>$verification_link</a></p>
                    <p><strong>This verification link will expire in 24 hours.</strong></p>
                    <p>Once verified, you'll be able to:</p>
                    <ul>
                        <li>Create posts and earn EXP</li>
                        <li>Comment on discussions</li>
                        <li>Vote on content</li>
                        <li>Build your reputation in our community</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p>Need help? Contact us at " . ADMIN_EMAIL . "</p>
                    <p>&copy; " . date('Y') . " Furom. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Try SMTP first, fall back to mail()
        if ($smtp_settings && $smtp_settings['enabled']) {
            return send_smtp_email($email, $subject, $message, $smtp_settings);
        } else {
            return send_basic_email($email, $subject, $message);
        }
        
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}

function get_smtp_settings() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM settings WHERE setting_key LIKE 'smtp_%'");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[str_replace('smtp_', '', $row['setting_key'])] = $row['setting_value'];
        }
        
        return !empty($settings) ? $settings : null;
    } catch (Exception $e) {
        return null;
    }
}

function send_smtp_email($to, $subject, $message, $smtp_settings) {
    try {
        // Use PHPMailer or similar library for SMTP
        // This is a simplified version - in production, use a proper email library
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . ($smtp_settings['from_email'] ?? SMTP_USERNAME) . "\r\n";
        $headers .= "Reply-To: " . ($smtp_settings['from_email'] ?? SMTP_USERNAME) . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        // Add authentication if needed
        if (!empty($smtp_settings['username']) && !empty($smtp_settings['password'])) {
            $headers .= "Authorization: Basic " . base64_encode($smtp_settings['username'] . ':' . $smtp_settings['password']) . "\r\n";
        }
        
        return mail($to, $subject, $message, $headers);
        
    } catch (Exception $e) {
        error_log("SMTP email error: " . $e->getMessage());
        return false;
    }
}

function send_basic_email($to, $subject, $message) {
    try {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_USERNAME . "\r\n";
        $headers .= "Reply-To: " . SMTP_USERNAME . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        return mail($to, $subject, $message, $headers);
        
    } catch (Exception $e) {
        error_log("Basic email error: " . $e->getMessage());
        return false;
    }
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