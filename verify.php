<?php
require_once 'config.php';

$message = '';
$message_type = '';
$token = sanitize_input($_GET['token'] ?? '');

if (empty($token)) {
    $message = 'Invalid verification link.';
    $message_type = 'error';
} else {
    // Check if token exists and is valid
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE verification_token = ? AND verified = 0");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Verify the user
        $update_stmt = $pdo->prepare("UPDATE users SET verified = 1, verification_token = NULL WHERE id = ?");
        if ($update_stmt->execute([$user['id']])) {
            $message = 'Email verified successfully! Welcome to Furom, ' . htmlspecialchars($user['username']) . '!';
            $message_type = 'success';
            
            // Auto-login the user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['login_time'] = time();
        } else {
            $message = 'Verification failed. Please try again or contact support.';
            $message_type = 'error';
        }
    } else {
        $message = 'Invalid or expired verification token.';
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="cyber-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1><i class="fas fa-robot"></i> FUROM</h1>
                    <span class="tagline">Futuristic Community Platform</span>
                </div>
                <nav class="main-nav">
                    <a href="index.php" class="nav-link">Home</a>
                </nav>
                <div class="user-actions">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="logout.php" class="btn btn-outline">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline">Login</a>
                        <a href="register.php" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        <div class="container">
            <div class="form-container" style="text-align: center; max-width: 500px;">
                <?php if ($message_type === 'success'): ?>
                    <div style="color: var(--success); margin-bottom: 2rem;">
                        <i class="fas fa-check-circle fa-3x" style="margin-bottom: 1rem;"></i>
                        <h2>Email Verified!</h2>
                        <p style="font-size: 1.2rem; margin: 1rem 0;"><?php echo htmlspecialchars($message); ?></p>
                        <div style="background: rgba(0, 255, 157, 0.1); border: 1px solid var(--success); border-radius: 10px; padding: 1.5rem; margin: 1.5rem 0;">
                            <h3><i class="fas fa-trophy"></i> Welcome Bonus!</h3>
                            <p>You've earned <strong>50 EXP</strong> for verifying your email!</p>
                            <p style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 0.5rem;">
                                Start exploring and earn more EXP by posting and commenting.
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="color: var(--danger); margin-bottom: 2rem;">
                        <i class="fas fa-exclamation-triangle fa-3x" style="margin-bottom: 1rem;"></i>
                        <h2>Verification Failed</h2>
                        <p style="font-size: 1.2rem; margin: 1rem 0;"><?php echo htmlspecialchars($message); ?></p>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 2rem;">
                    <?php if ($message_type === 'success'): ?>
                        <a href="index.php" class="btn btn-primary" style="margin: 0.5rem;">
                            <i class="fas fa-home"></i> Go to Homepage
                        </a>
                        <a href="create-post.php" class="btn btn-outline" style="margin: 0.5rem;">
                            <i class="fas fa-plus"></i> Create Your First Post
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary" style="margin: 0.5rem;">
                            <i class="fas fa-user-plus"></i> Try Registering Again
                        </a>
                        <a href="index.php" class="btn btn-outline" style="margin: 0.5rem;">
                            <i class="fas fa-home"></i> Return Home
                        </a>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color); color: var(--text-secondary);">
                    <p><i class="fas fa-question-circle"></i> Need help?</p>
                    <p>Contact us at <a href="mailto:<?php echo ADMIN_EMAIL; ?>" style="color: var(--primary);"><?php echo ADMIN_EMAIL; ?></a></p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="cyber-footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Furom. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    
    <?php if ($message_type === 'success'): ?>
    <script>
        // Add welcome EXP to user
        <?php if(isset($_SESSION['user_id'])): ?>
        fetch('ajax/add-exp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                amount: 50,
                reason: 'Email verification bonus'
            })
        }).catch(function(error) {
            console.log('Could not add welcome EXP:', error);
        });
        <?php endif; ?>
    </script>
    <?php endif; ?>
</body>
</html>