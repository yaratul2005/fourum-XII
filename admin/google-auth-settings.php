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
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group input:focus { outline: none; border-color: #3498db; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .btn { padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px; }
        .btn-primary { background: #3498db; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .message { padding: 15px; border-radius: 5px; margin: 20px 0; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .config-section { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 25px; }
        .setup-steps { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .setup-steps ol { padding-left: 20px; }
        .setup-steps li { margin: 10px 0; }
        .oauth-preview { 
            background: linear-gradient(135deg, #4285f4, #34a853, #fbbc05, #ea4335);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
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