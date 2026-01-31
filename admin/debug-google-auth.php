<?php
// Debug Google OAuth Configuration - Diagnostic Tool
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
    
    // Generate CSRF token
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generate_token();
    }
    
    $debug_info = [];
    $message = '';
    $message_type = '';
    
    // Debug database connection
    $debug_info['database_connection'] = 'Connected';
    try {
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch();
        $debug_info['mysql_version'] = $version['version'];
    } catch (Exception $e) {
        $debug_info['database_connection'] = 'Error: ' . $e->getMessage();
    }
    
    // Check settings table
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
        $debug_info['settings_table_exists'] = $stmt->rowCount() > 0 ? 'Yes' : 'No';
        
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("DESCRIBE settings");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $debug_info['settings_columns'] = implode(', ', $columns);
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM settings");
            $count = $stmt->fetch();
            $debug_info['settings_row_count'] = $count['count'];
            
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'google_%'");
            $google_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $debug_info['current_google_settings'] = $google_settings;
        }
    } catch (Exception $e) {
        $debug_info['settings_table_check'] = 'Error: ' . $e->getMessage();
    }
    
    // Handle manual save test
    if (isset($_POST['test_save'])) {
        try {
            // Validate CSRF
            if (!isset($_POST['csrf_token']) || !verify_token($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }
            
            $test_config = [
                'client_id' => 'test_client_id_123',
                'client_secret' => 'test_client_secret_123',
                'redirect_uri' => 'https://test.example.com/callback',
                'enabled' => 1
            ];
            
            $debug_info['save_attempt'] = 'Starting save attempt...';
            
            // Try to save
            $save_result = save_google_config_debug($test_config);
            $debug_info['save_result'] = $save_result ? 'Success' : 'Failed';
            
            if ($save_result) {
                $message = 'Test configuration saved successfully!';
                $message_type = 'success';
            } else {
                throw new Exception('Save operation returned false');
            }
            
        } catch (Exception $e) {
            $message = 'Test save failed: ' . $e->getMessage();
            $message_type = 'error';
            $debug_info['save_error'] = $e->getMessage();
        }
    }
    
    // Handle form save
    if (isset($_POST['save_google_auth'])) {
        try {
            // Validate CSRF
            if (!isset($_POST['csrf_token']) || !verify_token($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }
            
            $google_config = [
                'client_id' => trim($_POST['google_client_id'] ?? ''),
                'client_secret' => trim($_POST['google_client_secret'] ?? ''),
                'redirect_uri' => trim($_POST['google_redirect_uri'] ?? ''),
                'enabled' => isset($_POST['google_enabled']) ? 1 : 0
            ];
            
            $debug_info['form_data'] = $google_config;
            
            if (save_google_config_debug($google_config)) {
                $message = 'Google OAuth configuration saved successfully!';
                $message_type = 'success';
            } else {
                throw new Exception('Failed to save configuration');
            }
            
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
            $debug_info['save_error'] = $e->getMessage();
        }
    }
    
    // Get current configuration
    $current_google = get_google_config_debug();
    $debug_info['retrieved_config'] = $current_google;
    
} catch (Exception $e) {
    $fatal_error = $e->getMessage();
    $debug_info['fatal_error'] = $e->getMessage();
}

ob_end_flush();

function get_google_config_debug() {
    global $pdo, $debug_info;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
        if ($stmt->rowCount() == 0) {
            $debug_info['config_check'] = 'Settings table does not exist';
            return [
                'enabled' => 0,
                'client_id' => '',
                'client_secret' => '',
                'redirect_uri' => ''
            ];
        }
        
        $stmt = $pdo->prepare("SELECT * FROM settings WHERE setting_key LIKE 'google_%'");
        $stmt->execute();
        $config = [];
        while ($row = $stmt->fetch()) {
            $config[str_replace('google_', '', $row['setting_key'])] = $row['setting_value'];
        }
        $debug_info['config_check'] = 'Successfully retrieved ' . count($config) . ' settings';
        return $config;
    } catch (Exception $e) {
        $debug_info['config_check'] = 'Error: ' . $e->getMessage();
        return [
            'enabled' => 0,
            'client_id' => '',
            'client_secret' => '',
            'redirect_uri' => ''
        ];
    }
}

function save_google_config_debug($config) {
    global $pdo, $debug_info;
    try {
        // Check/create table
        $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
        if ($stmt->rowCount() == 0) {
            $create_sql = "
                CREATE TABLE IF NOT EXISTS `settings` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `setting_key` varchar(100) NOT NULL UNIQUE,
                    `setting_value` text,
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    INDEX `idx_setting_key` (`setting_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $pdo->exec($create_sql);
            $debug_info['table_creation'] = 'Created settings table';
        }
        
        $saved_count = 0;
        foreach ($config as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $result = $stmt->execute(["google_$key", $value, $value]);
            if ($result) {
                $saved_count++;
            }
        }
        
        $debug_info['save_details'] = "Saved $saved_count out of " . count($config) . " settings";
        return $saved_count > 0;
        
    } catch (Exception $e) {
        $debug_info['save_exception'] = $e->getMessage();
        error_log("Google config save debug error: " . $e->getMessage());
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Google Auth Settings</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .debug-section { background: #1e293b; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .debug-item { margin: 10px 0; padding: 10px; background: rgba(255,255,255,0.1); border-radius: 5px; }
        .debug-label { font-weight: bold; color: #00f5ff; }
        .debug-value { color: #a0a0c0; margin-left: 10px; }
        .success { color: #00ff9d; }
        .error { color: #ff4757; }
        .warning { color: #ffcc00; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-bug"></i> DEBUG</h2>
                <span>V4.0</span>
            </div>
            <nav class="sidebar-nav">
                <a href="google-auth-settings.php" class="nav-item">
                    <i class="fab fa-google"></i> Normal Settings
                </a>
                <a href="debug-google-auth.php" class="nav-item active">
                    <i class="fas fa-bug"></i> Debug Mode
                </a>
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="../index.php" class="nav-item">
                    <i class="fas fa-arrow-left"></i> Back to Site
                </a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <h1><i class="fas fa-bug"></i> Google Auth Debug Tool</h1>
                <p>Diagnostic tool for Google OAuth configuration issues</p>
            </header>

            <div class="admin-content">
                <?php if (isset($fatal_error)): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-triangle"></i> Fatal Error: <?php echo htmlspecialchars($fatal_error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($message): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="debug-section">
                    <h2><i class="fas fa-database"></i> Database Status</h2>
                    <?php foreach ($debug_info as $key => $value): ?>
                        <div class="debug-item">
                            <span class="debug-label"><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</span>
                            <?php if (is_array($value)): ?>
                                <pre class="debug-value"><?php echo htmlspecialchars(print_r($value, true)); ?></pre>
                            <?php else: ?>
                                <span class="debug-value <?php 
                                    echo strpos($value, 'Error') !== false ? 'error' : 
                                        (strpos($value, 'Success') !== false ? 'success' : 
                                        (strpos($value, 'Warning') !== false ? 'warning' : '')); 
                                ?>"><?php echo htmlspecialchars($value); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="debug-section">
                    <h2><i class="fas fa-cogs"></i> Configuration Form</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="google_enabled" <?php echo $current_google['enabled'] ? 'checked' : ''; ?>>
                                Enable Google Login
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>Google Client ID</label>
                            <input type="text" name="google_client_id" value="<?php echo htmlspecialchars($current_google['client_id'] ?? ''); ?>" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Google Client Secret</label>
                            <input type="password" name="google_client_secret" value="<?php echo htmlspecialchars($current_google['client_secret'] ?? ''); ?>" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Redirect URI</label>
                            <input type="text" name="google_redirect_uri" value="<?php echo htmlspecialchars($current_google['redirect_uri'] ?? ''); ?>" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="save_google_auth" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Configuration
                            </button>
                            <button type="submit" name="test_save" class="btn btn-secondary">
                                <i class="fas fa-vial"></i> Test Save
                            </button>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>