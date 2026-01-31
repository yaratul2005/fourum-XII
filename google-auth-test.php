<?php
// Google Authentication Test Page
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once 'config.php';
require_once 'includes/functions.php';

// Check if user is already logged in
$is_logged_in = is_logged_in();
$current_user = $is_logged_in ? get_user_data(get_current_user_id()) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Auth Test - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--card-bg);
            border-radius: 15px;
            border: 1px solid var(--border-color);
        }
        .status-item {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .status-success { background: rgba(16, 185, 129, 0.1); border-color: #10b981; }
        .status-error { background: rgba(239, 68, 68, 0.1); border-color: #ef4444; }
        .status-warning { background: rgba(245, 158, 11, 0.1); border-color: #f59e0b; }
        .test-button {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #4285f4, #34a853);
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(66, 133, 244, 0.3);
            border: 2px solid transparent;
            margin: 10px 0;
        }
        .test-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(66, 133, 244, 0.4);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-container">
        <div class="container">
            <div class="test-container">
                <h1><i class="fab fa-google"></i> Google Authentication Test</h1>
                <p>Test the Google OAuth login flow and configuration.</p>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="status-item status-error">
                        <h3><i class="fas fa-exclamation-circle"></i> Error</h3>
                        <p><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
                        <?php unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="status-item status-error">
                        <h3><i class="fas fa-exclamation-circle"></i> Authentication Error</h3>
                        <p>Error code: <?php echo htmlspecialchars($_GET['error']); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($is_logged_in): ?>
                    <div class="status-item status-success">
                        <h3><i class="fas fa-user-check"></i> Logged In</h3>
                        <p>Welcome, <?php echo htmlspecialchars($current_user['username']); ?>!</p>
                        <p>Email: <?php echo htmlspecialchars($current_user['email']); ?></p>
                        <p>EXP: <?php echo $current_user['exp']; ?></p>
                        <a href="logout.php" class="btn btn-outline">Logout</a>
                    </div>
                <?php else: ?>
                    <div class="status-item status-warning">
                        <h3><i class="fas fa-user-times"></i> Not Logged In</h3>
                        <p>You are currently not logged in.</p>
                    </div>
                <?php endif; ?>
                
                <div class="status-item">
                    <h3><i class="fas fa-cogs"></i> Configuration Status</h3>
                    <?php
                    try {
                        // Check settings table
                        $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
                        if ($stmt->rowCount() > 0) {
                            echo "<p class='status-success'><i class='fas fa-check'></i> Settings table exists</p>";
                            
                            // Check Google configuration
                            $stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'google_%'");
                            $google_config = [];
                            while ($row = $stmt->fetch()) {
                                $google_config[str_replace('google_', '', $row['setting_key'])] = $row['setting_value'];
                            }
                            
                            if (!empty($google_config['client_id']) && !empty($google_config['client_secret'])) {
                                echo "<p class='status-success'><i class='fas fa-check'></i> Google credentials configured</p>";
                                
                                if ($google_config['enabled'] == '1') {
                                    echo "<p class='status-success'><i class='fas fa-check'></i> Google authentication enabled</p>";
                                    
                                    // Test the login endpoint
                                    $test_url = SITE_URL . '/auth/google/login.php';
                                    echo "<p class='status-success'><i class='fas fa-check'></i> Login endpoint: <a href='$test_url' target='_blank'>$test_url</a></p>";
                                } else {
                                    echo "<p class='status-warning'><i class='fas fa-exclamation-triangle'></i> Google authentication disabled</p>";
                                }
                            } else {
                                echo "<p class='status-error'><i class='fas fa-times'></i> Google credentials missing</p>";
                                echo "<p><a href='/admin/google-auth-settings.php'>Configure Google Auth</a></p>";
                            }
                        } else {
                            echo "<p class='status-error'><i class='fas fa-times'></i> Settings table missing</p>";
                            echo "<p><a href='/admin/init-settings-table.php'>Initialize Settings Table</a></p>";
                        }
                    } catch (Exception $e) {
                        echo "<p class='status-error'><i class='fas fa-times'></i> Database error: " . $e->getMessage() . "</p>";
                    }
                    ?>
                </div>
                
                <div class="status-item">
                    <h3><i class="fas fa-flask"></i> Test Functions</h3>
                    <p>Try the Google login flow:</p>
                    
                    <a href="auth/google/login.php?redirect=/google-auth-test.php" class="test-button">
                        <i class="fab fa-google"></i>
                        <span>Test Google Login</span>
                    </a>
                    
                    <p style="margin-top: 20px;"><small>This will redirect you to Google for authentication, then back to this page.</small></p>
                </div>
                
                <div class="status-item">
                    <h3><i class="fas fa-info-circle"></i> Debug Information</h3>
                    <ul>
                        <li>SITE_URL: <?php echo defined('SITE_URL') ? SITE_URL : 'Not defined'; ?></li>
                        <li>Current Domain: <?php echo $_SERVER['HTTP_HOST'] ?? 'Unknown'; ?></li>
                        <li>HTTPS: <?php echo !empty($_SERVER['HTTPS']) ? 'Yes' : 'No'; ?></li>
                        <li>Request URI: <?php echo $_SERVER['REQUEST_URI'] ?? 'Unknown'; ?></li>
                    </ul>
                </div>
                
                <div style="margin-top: 2rem; text-align: center;">
                    <a href="/" class="btn btn-outline"><i class="fas fa-home"></i> Back to Homepage</a>
                    <a href="/admin/dashboard.php" class="btn btn-primary"><i class="fas fa-cog"></i> Admin Panel</a>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>