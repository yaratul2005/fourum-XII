<?php
require_once 'config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $errors[] = 'Username must be between 3 and 20 characters';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // Check if user already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists';
        }
    }
    
    // Create user if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $verification_token = generate_token();
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, verification_token, created_at) VALUES (?, ?, ?, ?, NOW())");
        
        if ($stmt->execute([$username, $email, $hashed_password, $verification_token])) {
            // Send verification email
            if (send_verification_email($email, $verification_token)) {
                $success = 'Registration successful! Please check your email to verify your account.';
            } else {
                $errors[] = 'Failed to send verification email. Please contact support.';
            }
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
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
                    <a href="login.php" class="btn btn-outline">Login</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        <div class="container">
            <div class="form-container">
                <h2><i class="fas fa-user-plus"></i> Create Your Account</h2>
                <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                    Join our futuristic community and start earning EXP today!
                </p>
                
                <?php if (!empty($errors)): ?>
                    <div class="error-messages" style="background: rgba(255, 71, 87, 0.2); border: 1px solid var(--danger); border-radius: 10px; padding: 1rem; margin-bottom: 1rem;">
                        <h4 style="color: var(--danger); margin-bottom: 0.5rem;"><i class="fas fa-exclamation-circle"></i> Please fix the following errors:</h4>
                        <ul style="color: var(--text-secondary);">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message" style="background: rgba(0, 255, 157, 0.2); border: 1px solid var(--success); border-radius: 10px; padding: 1rem; margin-bottom: 1rem; color: var(--success);">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" data-validate data-auto-save="true" id="register-form">
                    <div class="form-group">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" id="username" name="username" class="form-input" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                               required minlength="3" maxlength="20" 
                               pattern="[a-zA-Z0-9_]+" 
                               title="3-20 characters, letters, numbers, and underscores only">
                        <small style="color: var(--text-secondary); font-size: 0.8rem;">
                            3-20 characters, letters, numbers, and underscores only
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-input" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" id="password" name="password" class="form-input" 
                               required minlength="6">
                        <small style="color: var(--text-secondary); font-size: 0.8rem;">
                            At least 6 characters
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-user-plus"></i> Register Account
                        </button>
                    </div>
                </form>
                
                <div style="text-align: center; margin-top: 2rem; color: var(--text-secondary);">
                    <p>Already have an account? <a href="login.php" style="color: var(--primary);">Login here</a></p>
                    <p style="margin-top: 1rem; font-size: 0.9rem;">
                        By registering, you agree to our <a href="terms.php" style="color: var(--primary);">Terms of Service</a> 
                        and <a href="privacy.php" style="color: var(--primary);">Privacy Policy</a>
                    </p>
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
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
                this.parentElement.querySelector('.error-message')?.remove();
                const errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.textContent = 'Passwords do not match';
                this.parentElement.appendChild(errorSpan);
                this.classList.add('error');
            } else {
                this.setCustomValidity('');
                this.parentElement.querySelector('.error-message')?.remove();
                this.classList.remove('error');
            }
        });
        
        // Real-time username availability check
        let usernameTimeout;
        document.getElementById('username').addEventListener('input', function() {
            clearTimeout(usernameTimeout);
            const username = this.value.trim();
            
            if (username.length >= 3) {
                usernameTimeout = setTimeout(() => {
                    checkUsernameAvailability(username);
                }, 500);
            }
        });
        
        function checkUsernameAvailability(username) {
            fetch('ajax/check-username.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({username: username})
            })
            .then(response => response.json())
            .then(data => {
                const usernameField = document.getElementById('username');
                const parent = usernameField.parentElement;
                parent.querySelector('.availability-message')?.remove();
                
                const messageSpan = document.createElement('span');
                messageSpan.className = 'availability-message';
                messageSpan.style.fontSize = '0.8rem';
                messageSpan.style.marginTop = '0.25rem';
                messageSpan.style.display = 'block';
                
                if (data.available) {
                    messageSpan.style.color = 'var(--success)';
                    messageSpan.innerHTML = '<i class="fas fa-check"></i> Username available';
                } else {
                    messageSpan.style.color = 'var(--danger)';
                    messageSpan.innerHTML = '<i class="fas fa-times"></i> Username taken';
                    usernameField.classList.add('error');
                }
                
                parent.appendChild(messageSpan);
            });
        }
    </script>
</body>
</html>