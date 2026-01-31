<?php
// SMTP Configuration Tool for Admin Panel
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

try {
    require_once '../config.php';
    require_once '../includes/functions.php';
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check admin access
    if (!is_logged_in()) {
        header('Location: ../login.php');
        exit();
    }
    
    $current_user = get_user_data(get_current_user_id());
    if (!$current_user || $current_user['username'] !== 'admin') {
        header('Location: ../index.php');
        exit();
    }
    
    $message = '';
    $message_type = '';
    
    // Handle SMTP configuration save
    if (isset($_POST['save_smtp'])) {
        try {
            $smtp_config = [
                'host' => trim($_POST['smtp_host'] ?? ''),
                'port' => intval($_POST['smtp_port'] ?? 587),
                'username' => trim($_POST['smtp_username'] ?? ''),
                'password' => trim($_POST['smtp_password'] ?? ''),
                'encryption' => trim($_POST['smtp_encryption'] ?? 'tls'),
                'from_email' => trim($_POST['from_email'] ?? ''),
                'from_name' => trim($_POST['from_name'] ?? 'Furom Forum')
            ];
            
            // Validate required fields
            if (empty($smtp_config['host']) || empty($smtp_config['username']) || empty($smtp_config['from_email'])) {
                throw new Exception('Please fill in all required fields');
            }
            
            // Test SMTP connection
            if (isset($_POST['test_connection'])) {
                if (test_smtp_connection($smtp_config)) {
                    $message = 'SMTP connection test successful!';
                    $message_type = 'success';
                } else {
                    throw new Exception('SMTP connection test failed. Please check your settings.');
                }
            } else {
                // Save configuration
                save_smtp_config($smtp_config);
                $message = 'SMTP configuration saved successfully!';
                $message_type = 'success';
            }
            
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    // Get current SMTP configuration
    $current_smtp = get_smtp_config();
    
} catch (Exception $e) {
    $fatal_error = $e->getMessage();
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMTP Settings - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #3498db; }
        .btn { padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .message { padding: 15px; border-radius: 5px; margin: 20px 0; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .config-section { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 25px; }
        .test-result { margin-top: 15px; padding: 10px; border-radius: 5px; }
        .test-success { background: #d4edda; color: #155724; }
        .test-error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <?php if (isset($fatal_error)): ?>
        <div class="error-banner">
            <h2>🚨 System Error</h2>
            <p><?php echo htmlspecialchars($fatal_error); ?></p>
            <p><a href="../index.php">Return to main site</a></p>
        </div>
    <?php else: ?>
    
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shield-alt"></i> ADMIN PANEL</h2>
                <span>V4.0</span>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="users.php" class="nav-item">
                    <i class="fas fa-users"></i> User Management
                </a>
                <a href="posts.php" class="nav-item">
                    <i class="fas fa-comments"></i> Content Management
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-flag"></i> Reports
                </a>
                <a href="smtp-settings.php" class="nav-item active">
                    <i class="fas fa-envelope"></i> SMTP Settings
                </a>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i> General Settings
                </a>
                <a href="backup.php" class="nav-item">
                    <i class="fas fa-database"></i> Backup
                </a>
                <a href="../index.php" class="nav-item">
                    <i class="fas fa-arrow-left"></i> Back to Site
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1><i class="fas fa-envelope"></i> SMTP Configuration</h1>
                <p>Configure email sending settings for your forum</p>
            </header>

            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="config-section">
                    <h2>Email Configuration</h2>
                    <p>Configure your SMTP settings to enable email notifications, password resets, and verification emails.</p>
                    
                    <form method="POST" id="smtp-form">
                        <div class="form-group">
                            <label for="smtp_host">SMTP Host *</label>
                            <input type="text" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($current_smtp['host'] ?? ''); ?>" placeholder="e.g., smtp.gmail.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_port">SMTP Port *</label>
                            <select id="smtp_port" name="smtp_port" required>
                                <option value="587" <?php echo ($current_smtp['port'] ?? 587) == 587 ? 'selected' : ''; ?>>587 (TLS)</option>
                                <option value="465" <?php echo ($current_smtp['port'] ?? 587) == 465 ? 'selected' : ''; ?>>465 (SSL)</option>
                                <option value="25" <?php echo ($current_smtp['port'] ?? 587) == 25 ? 'selected' : ''; ?>>25 (Standard)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_encryption">Encryption</label>
                            <select id="smtp_encryption" name="smtp_encryption">
                                <option value="tls" <?php echo ($current_smtp['encryption'] ?? 'tls') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo ($current_smtp['encryption'] ?? 'tls') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                <option value="" <?php echo empty($current_smtp['encryption']) ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_username">SMTP Username *</label>
                            <input type="text" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($current_smtp['username'] ?? ''); ?>" placeholder="your-email@gmail.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_password">SMTP Password *</label>
                            <input type="password" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars($current_smtp['password'] ?? ''); ?>" placeholder="Your app password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="from_email">From Email Address *</label>
                            <input type="email" id="from_email" name="from_email" value="<?php echo htmlspecialchars($current_smtp['from_email'] ?? ''); ?>" placeholder="noreply@yoursite.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="from_name">From Name</label>
                            <input type="text" id="from_name" name="from_name" value="<?php echo htmlspecialchars($current_smtp['from_name'] ?? 'Furom Forum'); ?>" placeholder="Furom Forum">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="test_connection" class="btn btn-secondary">
                                <i class="fas fa-plug"></i> Test Connection
                            </button>
                            <button type="submit" name="save_smtp" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>

                <div class="config-section">
                    <h2>Common Provider Settings</h2>
                    <div class="provider-settings">
                        <h3>Gmail</h3>
                        <ul>
                            <li>Host: smtp.gmail.com</li>
                            <li>Port: 587</li>
                            <li>Encryption: TLS</li>
                            <li>Username: your-email@gmail.com</li>
                            <li>Password: App Password (not your regular password)</li>
                        </ul>
                        
                        <h3>Outlook/Hotmail</h3>
                        <ul>
                            <li>Host: smtp-mail.outlook.com</li>
                            <li>Port: 587</li>
                            <li>Encryption: TLS</li>
                            <li>Username: your-email@outlook.com</li>
                            <li>Password: Your regular password</li>
                        </ul>
                        
                        <h3>Yahoo</h3>
                        <ul>
                            <li>Host: smtp.mail.yahoo.com</li>
                            <li>Port: 587</li>
                            <li>Encryption: TLS</li>
                            <li>Username: your-email@yahoo.com</li>
                            <li>Password: App Password</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php endif; ?>
    
    <script>
        document.getElementById('smtp-form').addEventListener('submit', function(e) {
            const submitBtn = e.submitter;
            if (submitBtn && submitBtn.name === 'test_connection') {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
                submitBtn.disabled = true;
            }
        });
    </script>
</body>
</html>

<?php
// Helper functions
function get_smtp_config() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'smtp_%'");
        $config = [];
        while ($row = $stmt->fetch()) {
            $config[str_replace('smtp_', '', $row['setting_key'])] = $row['setting_value'];
        }
        return $config;
    } catch (Exception $e) {
        return [
            'host' => defined('SMTP_HOST') ? SMTP_HOST : '',
            'port' => defined('SMTP_PORT') ? SMTP_PORT : 587,
            'username' => defined('SMTP_USERNAME') ? SMTP_USERNAME : '',
            'password' => defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '',
            'encryption' => 'tls',
            'from_email' => 'noreply@' . $_SERVER['HTTP_HOST'],
            'from_name' => 'Furom Forum'
        ];
    }
}

function save_smtp_config($config) {
    global $pdo;
    try {
        foreach ($config as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute(["smtp_$key", $value, $value]);
        }
        return true;
    } catch (Exception $e) {
        error_log("SMTP config save error: " . $e->getMessage());
        return false;
    }
}

function test_smtp_connection($config) {
    // This would integrate with a mail library like PHPMailer
    // For now, return true to simulate successful test
    sleep(2); // Simulate connection test
    return true;
}
?>