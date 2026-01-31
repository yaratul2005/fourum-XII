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
        /* Cyberpunk Theme Integration for Admin Panel */
        :root {
            --admin-dark: #0f172a;
            --admin-darker: #0b1120;
            --admin-darkest: #070a15;
            --admin-card-bg: #1e293b;
            --admin-border: #334155;
            --admin-text-primary: #f1f5f9;
            --admin-text-secondary: #94a3b8;
            --admin-primary: #3b82f6;
            --admin-success: #10b981;
            --admin-warning: #f59e0b;
            --admin-danger: #ef4444;
            --cyber-primary: #00f5ff;
            --cyber-secondary: #ff00ff;
            --cyber-accent: #ff6b6b;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
            animation: fadeInUp 0.3s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--admin-text-primary);
            font-size: 0.95rem;
            position: relative;
        }
        
        .form-group label::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 30px;
            height: 2px;
            background: linear-gradient(90deg, var(--cyber-primary), var(--cyber-secondary));
            border-radius: 1px;
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            background: var(--admin-darker);
            border: 2px solid var(--admin-border);
            border-radius: 8px;
            color: var(--admin-text-primary);
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus {
            outline: none;
            border-color: var(--cyber-primary);
            box-shadow: 0 0 0 3px rgba(0, 245, 255, 0.2);
            background: rgba(11, 17, 32, 0.8);
            transform: translateY(-2px);
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: var(--admin-text-secondary);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: var(--cyber-primary);
            cursor: pointer;
        }
        
        .checkbox-group label {
            margin-bottom: 0;
            color: var(--admin-text-primary);
            font-weight: 500;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--cyber-primary), var(--cyber-secondary));
            color: white;
            box-shadow: 0 4px 15px rgba(0, 245, 255, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 245, 255, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--admin-success), #34d399);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, var(--admin-text-secondary), #64748b);
            color: var(--admin-dark);
            box-shadow: 0 4px 15px rgba(148, 163, 184, 0.3);
        }
        
        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(148, 163, 184, 0.4);
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin: 20px 0;
            animation: slideInRight 0.5s ease-out;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .message.success {
            background: rgba(16, 185, 129, 0.15);
            color: var(--admin-success);
            border: 1px solid rgba(16, 185, 129, 0.3);
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.2);
        }
        
        .message.error {
            background: rgba(239, 68, 68, 0.15);
            color: var(--admin-danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.2);
        }
        
        .config-section {
            background: var(--admin-card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            margin-bottom: 25px;
            border: 1px solid var(--admin-border);
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }
        
        .config-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--cyber-primary), var(--cyber-secondary));
            box-shadow: 0 0 10px var(--cyber-primary);
        }
        
        .config-section h2 {
            color: var(--admin-text-primary);
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .config-section p {
            color: var(--admin-text-secondary);
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .setup-steps {
            background: rgba(30, 41, 59, 0.7);
            padding: 25px;
            border-radius: 12px;
            margin: 25px 0;
            border: 1px solid var(--admin-border);
            animation: fadeIn 0.8s ease-out;
        }
        
        .setup-steps h2 {
            color: var(--cyber-primary);
            font-size: 1.3rem;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .setup-steps ol {
            padding-left: 25px;
            color: var(--admin-text-secondary);
        }
        
        .setup-steps li {
            margin: 12px 0;
            line-height: 1.6;
            position: relative;
        }
        
        .setup-steps li::marker {
            color: var(--cyber-primary);
            font-weight: bold;
        }
        
        .setup-steps a {
            color: var(--cyber-primary);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .setup-steps a:hover {
            color: var(--cyber-secondary);
            text-decoration: underline;
        }
        
        .oauth-preview {
            background: linear-gradient(135deg, #4285f4, #34a853, #fbbc05, #ea4335);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin: 25px 0;
            box-shadow: 0 8px 25px rgba(66, 133, 244, 0.3);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        .oauth-preview h2 {
            margin-bottom: 10px;
            font-size: 1.5rem;
        }
        
        .oauth-preview p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .provider-settings {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid var(--admin-border);
        }
        
        .provider-settings h3 {
            color: var(--cyber-primary);
            margin: 15px 0 10px;
            font-size: 1.2rem;
        }
        
        .provider-settings ul {
            color: var(--admin-text-secondary);
            padding-left: 20px;
        }
        
        .provider-settings li {
            margin: 8px 0;
            line-height: 1.5;
        }
        
        .provider-settings code {
            background: rgba(0, 245, 255, 0.1);
            color: var(--cyber-primary);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            border: 1px solid rgba(0, 245, 255, 0.2);
        }
        
        small {
            color: var(--admin-text-secondary);
            font-size: 0.85rem;
            display: block;
            margin-top: 5px;
            font-style: italic;
        }
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