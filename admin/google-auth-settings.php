<?php
// Google OAuth Configuration for Admin Panel
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
    
    // Handle Google OAuth configuration save
    if (isset($_POST['save_google_auth'])) {
        try {
            $google_config = [
                'client_id' => trim($_POST['google_client_id'] ?? ''),
                'client_secret' => trim($_POST['google_client_secret'] ?? ''),
                'redirect_uri' => trim($_POST['google_redirect_uri'] ?? ''),
                'enabled' => isset($_POST['google_enabled']) ? 1 : 0
            ];
            
            // Validate required fields if enabled
            if ($google_config['enabled'] && (empty($google_config['client_id']) || empty($google_config['client_secret']))) {
                throw new Exception('Please provide both Client ID and Client Secret when enabling Google Login');
            }
            
            // Save configuration
            save_google_config($google_config);
            $message = 'Google OAuth configuration saved successfully!';
            $message_type = 'success';
            
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    // Get current Google configuration
    $current_google = get_google_config();
    
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
    <title>Google Auth Settings - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Cyberpunk Theme Integration for Google Auth Settings */
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
            --google-red: #ea4335;
            --google-blue: #4285f4;
            --google-green: #34a853;
            --google-yellow: #fbbc05;
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
        
        .form-group input[readonly] {
            background: rgba(30, 41, 59, 0.5);
            color: var(--admin-text-secondary);
            cursor: not-allowed;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: linear-gradient(135deg, rgba(66, 133, 244, 0.15), rgba(52, 168, 83, 0.15));
            border-radius: 8px;
            border: 1px solid rgba(66, 133, 244, 0.3);
            margin-bottom: 20px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: var(--google-blue);
            cursor: pointer;
            transform: scale(1.1);
        }
        
        .checkbox-group label {
            margin-bottom: 0;
            color: var(--admin-text-primary);
            font-weight: 500;
            font-size: 1.1rem;
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
            background: linear-gradient(135deg, var(--google-blue), var(--google-green));
            color: white;
            box-shadow: 0 4px 15px rgba(66, 133, 244, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(66, 133, 244, 0.4);
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
            background: linear-gradient(90deg, var(--google-red), var(--google-blue), var(--google-green), var(--google-yellow));
            box-shadow: 0 0 10px var(--google-blue);
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
        
        .oauth-preview {
            background: linear-gradient(135deg, var(--google-blue), var(--google-green), var(--google-yellow), var(--google-red));
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            margin: 25px 0;
            box-shadow: 0 10px 30px rgba(66, 133, 244, 0.4);
            animation: gradientShift 3s ease-in-out infinite;
            position: relative;
            overflow: hidden;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .oauth-preview::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 0deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: rotate 4s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .oauth-preview h2 {
            margin-bottom: 15px;
            font-size: 1.8rem;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .oauth-preview p {
            opacity: 0.95;
            font-size: 1.1rem;
            position: relative;
            z-index: 2;
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .setup-steps ol {
            padding-left: 25px;
            color: var(--admin-text-secondary);
        }
        
        .setup-steps li {
            margin: 15px 0;
            line-height: 1.7;
            position: relative;
            padding-left: 10px;
        }
        
        .setup-steps li::marker {
            color: var(--google-blue);
            font-weight: bold;
        }
        
        .setup-steps li::before {
            content: '▶';
            color: var(--google-green);
            margin-right: 10px;
            font-size: 0.8rem;
        }
        
        .setup-steps a {
            color: var(--cyber-primary);
            text-decoration: none;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .setup-steps a:hover {
            color: var(--cyber-secondary);
            text-decoration: underline;
        }
        
        .setup-steps strong {
            color: var(--admin-text-primary);
            font-weight: 600;
        }
        
        small {
            color: var(--admin-text-secondary);
            font-size: 0.85rem;
            display: block;
            margin-top: 5px;
            font-style: italic;
        }
        
        code {
            background: rgba(0, 245, 255, 0.1);
            color: var(--cyber-primary);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            border: 1px solid rgba(0, 245, 255, 0.2);
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
                <a href="smtp-settings.php" class="nav-item">
                    <i class="fas fa-envelope"></i> SMTP Settings
                </a>
                <a href="google-auth-settings.php" class="nav-item active">
                    <i class="fab fa-google"></i> Google Auth
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
                <h1><i class="fab fa-google"></i> Google OAuth Configuration</h1>
                <p>Enable one-click Google login for your forum users</p>
            </header>

            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="oauth-preview">
                    <h2><i class="fab fa-google"></i> Sign in with Google</h2>
                    <p>Allow users to register and login using their Google accounts</p>
                </div>

                <div class="config-section">
                    <h2>Google OAuth Settings</h2>
                    
                    <form method="POST">
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="google_enabled" name="google_enabled" <?php echo $current_google['enabled'] ? 'checked' : ''; ?>>
                            <label for="google_enabled">Enable Google Login</label>
                        </div>
                        
                        <div class="form-group">
                            <label for="google_client_id">Google Client ID *</label>
                            <input type="text" id="google_client_id" name="google_client_id" value="<?php echo htmlspecialchars($current_google['client_id'] ?? ''); ?>" placeholder="Your Google OAuth Client ID">
                            <small>Get this from Google Cloud Console</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="google_client_secret">Google Client Secret *</label>
                            <input type="password" id="google_client_secret" name="google_client_secret" value="<?php echo htmlspecialchars($current_google['client_secret'] ?? ''); ?>" placeholder="Your Google OAuth Client Secret">
                            <small>This will be encrypted in the database</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="google_redirect_uri">Redirect URI</label>
                            <input type="text" id="google_redirect_uri" name="google_redirect_uri" value="<?php echo htmlspecialchars($current_google['redirect_uri'] ?? (defined('SITE_URL') ? SITE_URL : 'https://' . $_SERVER['HTTP_HOST']) . '/auth/google/callback.php'); ?>" readonly>
                            <small>This is automatically generated - copy this to your Google Console</small>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="save_google_auth" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Configuration
                            </button>
                        </div>
                    </form>
                </div>

                <div class="setup-steps">
                    <h2>Setup Instructions</h2>
                    <ol>
                        <li><strong>Create Google Project:</strong> Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                        <li><strong>Enable Google+ API:</strong> In APIs & Services → Library, enable Google+ API</li>
                        <li><strong>Create OAuth Credentials:</strong> Go to APIs & Services → Credentials → Create Credentials → OAuth client ID</li>
                        <li><strong>Configure Consent Screen:</strong> Set up the OAuth consent screen with your app details</li>
                        <li><strong>Set Application Type:</strong> Choose "Web application"</li>
                        <li><strong>Add Authorized Redirect URIs:</strong> Add the redirect URI shown above</li>
                        <li><strong>Copy Credentials:</strong> Copy the Client ID and Client Secret to the form above</li>
                        <li><strong>Enable Integration:</strong> Check "Enable Google Login" and save</li>
                    </ol>
                </div>

                <div class="config-section">
                    <h2>Required Scopes</h2>
                    <p>The following Google scopes will be requested:</p>
                    <ul>
                        <li><code>openid</code> - For OpenID Connect authentication</li>
                        <li><code>profile</code> - To get user's name and profile picture</li>
                        <li><code>email</code> - To get user's email address</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
    
    <?php endif; ?>
</body>
</html>

<?php
// Helper functions
function get_google_config() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'google_%'");
        $config = [];
        while ($row = $stmt->fetch()) {
            $config[str_replace('google_', '', $row['setting_key'])] = $row['setting_value'];
        }
        return $config;
    } catch (Exception $e) {
        return [
            'enabled' => 0,
            'client_id' => '',
            'client_secret' => '',
            'redirect_uri' => ''
        ];
    }
}

function save_google_config($config) {
    global $pdo;
    try {
        foreach ($config as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute(["google_$key", $value, $value]);
        }
        return true;
    } catch (Exception $e) {
        error_log("Google config save error: " . $e->getMessage());
        return false;
    }
}
?>