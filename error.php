<?php
$error_code = isset($_GET['code']) ? (int)$_GET['code'] : 404;
$error_messages = [
    403 => ['Forbidden', 'You don\'t have permission to access this resource'],
    404 => ['Page Not Found', 'The page you\'re looking for doesn\'t exist'],
    500 => ['Internal Server Error', 'Something went wrong on our end']
];

$message = $error_messages[$error_code] ?? $error_messages[404];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $message[0]; ?> - <?php echo SITE_NAME ?? 'Furom'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .error-container {
            text-align: center;
            padding: 3rem;
            min-height: 60vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .error-code {
            font-size: 6rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            text-shadow: var(--neon-glow);
        }
        
        .error-title {
            font-size: 2rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .error-message {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            max-width: 600px;
        }
        
        .error-icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 2rem;
            text-shadow: var(--neon-glow);
        }
    </style>
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
                    <?php if(isset($_SESSION['user_id'] ?? false)): ?>
                        <a href="profile.php" class="btn btn-outline">Profile</a>
                        <a href="logout.php" class="btn btn-primary">Logout</a>
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
            <div class="error-container">
                <?php if($error_code == 404): ?>
                    <div class="error-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                <?php elseif($error_code == 403): ?>
                    <div class="error-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                <?php else: ?>
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                <?php endif; ?>
                
                <div class="error-code"><?php echo $error_code; ?></div>
                <h1 class="error-title"><?php echo $message[0]; ?></h1>
                <p class="error-message"><?php echo $message[1]; ?></p>
                
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Return Home
                    </a>
                    <button onclick="history.back()" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Go Back
                    </button>
                </div>
                
                <?php if($error_code == 404): ?>
                    <div style="margin-top: 2rem; padding: 1.5rem; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 15px; max-width: 500px;">
                        <h3 style="color: var(--primary); margin-bottom: 1rem;"><i class="fas fa-lightbulb"></i> Helpful Links</h3>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <a href="create-post.php" style="color: var(--text-secondary); text-decoration: none; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-plus-circle"></i> Create a new post
                            </a>
                            <a href="index.php" style="color: var(--text-secondary); text-decoration: none; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-comments"></i> Browse recent discussions
                            </a>
                            <a href="register.php" style="color: var(--text-secondary); text-decoration: none; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-user-plus"></i> Join our community
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
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
</body>
</html>