<?php
// Resend Verification Email
require_once 'config.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$message_type = '';

// Handle resend request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        try {
            // Check if user exists and needs verification
            $stmt = $pdo->prepare("SELECT id, username, verification_token, verified FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                // Don't reveal if email exists for security
                $message = 'If an account exists with this email, a verification email has been sent.';
                $message_type = 'info';
            } elseif ($user['verified']) {
                $message = 'This account is already verified. You can login now.';
                $message_type = 'info';
            } else {
                // Generate new token if old one expired or resend existing
                $verification_token = $user['verification_token'] ?? generate_token();
                
                if (!$user['verification_token']) {
                    // Update with new token
                    $update_stmt = $pdo->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
                    $update_stmt->execute([$verification_token, $user['id']]);
                }
                
                // Send verification email
                if (send_verification_email($email, $verification_token)) {
                    $message = 'Verification email resent successfully! Please check your inbox and spam folder.';
                    $message_type = 'success';
                } else {
                    $message = 'We couldn\'t send the verification email. Please try again later or contact support.';
                    $message_type = 'error';
                    error_log("Failed to resend verification email to: " . $email);
                }
            }
        } catch (Exception $e) {
            $message = 'An error occurred. Please try again later.';
            $message_type = 'error';
            error_log("Resend verification error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-container">
        <div class="container">
            <div class="form-container" style="max-width: 500px; margin: 2rem auto;">
                <h2><i class="fas fa-paper-plane"></i> Resend Verification Email</h2>
                <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                    Enter your email address and we'll send you a new verification link.
                </p>
                
                <?php if ($message): ?>
                    <div class="alert <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                        <i class="fas fa-paper-plane"></i> Resend Verification Email
                    </button>
                </form>
                
                <div style="margin-top: 2rem; text-align: center; color: var(--text-secondary);">
                    <p>Already verified? <a href="login.php">Login here</a></p>
                    <p>Need a new account? <a href="register.php">Register here</a></p>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>