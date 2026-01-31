<?php
require_once 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $identifier = sanitize_input($_POST['identifier'] ?? ''); // Can be username or email
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    if (empty($identifier)) {
        $errors[] = 'Username or email is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    if (empty($errors)) {
        // Check if identifier is email or username
        $is_email = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $column = $is_email ? 'email' : 'username';
        
        $stmt = $pdo->prepare("SELECT id, username, password, verified FROM users WHERE $column = ?");
        $stmt->execute([$identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['verified']) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['login_time'] = time();
                
                // Update last login
                $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_stmt->execute([$user['id']]);
                
                // Set remember me cookie
                if ($remember_me) {
                    $token = generate_token();
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                    // Store token in database (you'd want to implement this properly)
                }
                
                // Redirect to intended page or home
                $redirect = $_GET['redirect'] ?? 'index.php';
                header("Location: $redirect");
                exit();
            } else {
                $errors[] = 'Please verify your email address before logging in.';
            }
        } else {
            $errors[] = 'Invalid username/email or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
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
                    <a href="register.php" class="btn btn-outline">Register</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        <div class="container">
            <div class="form-container">
                <h2><i class="fas fa-sign-in-alt"></i> Welcome Back</h2>
                <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                    Log in to continue your journey and earn more EXP!
                </p>
                
                <?php if (!empty($errors)): ?>
                    <div class="error-messages" style="background: rgba(255, 71, 87, 0.2); border: 1px solid var(--danger); border-radius: 10px; padding: 1rem; margin-bottom: 1rem;">
                        <ul style="color: var(--text-secondary); margin: 0;">
                            <?php foreach ($errors as $error): ?>
                                <li style="margin: 0.25rem 0;"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="identifier" class="form-label">Username or Email *</label>
                        <input type="text" id="identifier" name="identifier" class="form-input" 
                               value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" id="password" name="password" class="form-input" required>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">
                                <input type="checkbox" name="remember_me" style="margin: 0;">
                                Remember me
                            </label>
                            <a href="forgot-password.php" style="color: var(--primary); font-size: 0.9rem;">Forgot password?</a>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </div>
                </form>
                
                <div style="text-align: center; margin-top: 2rem; color: var(--text-secondary);">
                    <p>Don't have an account? <a href="register.php" style="color: var(--primary);">Register here</a></p>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                        <p style="font-size: 0.9rem;">
                            <i class="fas fa-shield-alt"></i> Secure login with encrypted connection
                        </p>
                    </div>
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
</body>
</html>