<?php
// Initialize Settings Table for Google Auth and Other Configurations
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
    $results = [];
    
    // Handle table initialization
    if (isset($_POST['init_settings'])) {
        try {
            // Check if table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
            if ($stmt->rowCount() == 0) {
                // Create settings table
                $create_table_sql = "
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
                
                $pdo->exec($create_table_sql);
                $results[] = ['success', 'Settings table created successfully'];
            } else {
                $results[] = ['info', 'Settings table already exists'];
            }
            
            // Insert default Google Auth settings if they don't exist
            $default_settings = [
                'google_enabled' => '0',
                'google_client_id' => '',
                'google_client_secret' => '',
                'google_redirect_uri' => ''
            ];
            
            $inserted_count = 0;
            foreach ($default_settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
                $result = $stmt->execute([$key, $value]);
                if ($result) {
                    $inserted_count++;
                }
            }
            
            if ($inserted_count > 0) {
                $results[] = ['success', "Inserted $inserted_count default settings"];
            } else {
                $results[] = ['info', 'Default settings already exist'];
            }
            
            $message = 'Settings table initialization completed!';
            $message_type = 'success';
            
        } catch (Exception $e) {
            $message = 'Error initializing settings table: ' . $e->getMessage();
            $message_type = 'error';
            $results[] = ['error', $e->getMessage()];
        }
    }
    
    // Check current status
    $status = [];
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
        $status['table_exists'] = $stmt->rowCount() > 0;
        
        if ($status['table_exists']) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM settings");
            $count = $stmt->fetch();
            $status['row_count'] = $count['count'];
            
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'google_%'");
            $google_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $status['google_settings'] = $google_settings;
        }
    } catch (Exception $e) {
        $status['error'] = $e->getMessage();
    }
    
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
    <title>Initialize Settings Table</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-item { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .status-success { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .status-error { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
        .status-info { background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); }
        .result-list { margin: 20px 0; }
        .result-item { padding: 8px; margin: 5px 0; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-database"></i> SETUP</h2>
                <span>V4.0</span>
            </div>
            <nav class="sidebar-nav">
                <a href="init-settings-table.php" class="nav-item active">
                    <i class="fas fa-table"></i> Init Settings Table
                </a>
                <a href="google-auth-settings.php" class="nav-item">
                    <i class="fab fa-google"></i> Google Auth Settings
                </a>
                <a href="debug-google-auth.php" class="nav-item">
                    <i class="fas fa-bug"></i> Debug Google Auth
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
                <h1><i class="fas fa-table"></i> Settings Table Initialization</h1>
                <p>Initialize database table for configuration storage</p>
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

                <div class="config-section">
                    <h2><i class="fas fa-info-circle"></i> Current Status</h2>
                    <?php if (isset($status['error'])): ?>
                        <div class="status-item status-error">
                            <i class="fas fa-exclamation-circle"></i> Database Error: <?php echo htmlspecialchars($status['error']); ?>
                        </div>
                    <?php else: ?>
                        <div class="status-item <?php echo $status['table_exists'] ? 'status-success' : 'status-error'; ?>">
                            <i class="fas fa-<?php echo $status['table_exists'] ? 'check' : 'times'; ?>"></i>
                            Settings Table: <?php echo $status['table_exists'] ? 'Exists' : 'Does Not Exist'; ?>
                        </div>
                        
                        <?php if ($status['table_exists']): ?>
                            <div class="status-item status-info">
                                <i class="fas fa-list"></i> Settings Records: <?php echo $status['row_count'] ?? 0; ?>
                            </div>
                            
                            <?php if (!empty($status['google_settings'])): ?>
                                <div class="status-item status-info">
                                    <i class="fab fa-google"></i> Google Settings Found:
                                    <ul style="margin-top: 10px;">
                                        <?php foreach ($status['google_settings'] as $setting): ?>
                                            <li><?php echo htmlspecialchars($setting['setting_key']); ?>: <?php echo $setting['setting_value'] ? '[VALUE SET]' : '[EMPTY]'; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($results)): ?>
                    <div class="config-section">
                        <h2><i class="fas fa-tasks"></i> Initialization Results</h2>
                        <div class="result-list">
                            <?php foreach ($results as $result): ?>
                                <div class="result-item status-<?php echo $result[0]; ?>">
                                    <i class="fas fa-<?php echo $result[0] === 'success' ? 'check' : ($result[0] === 'error' ? 'times' : 'info'); ?>"></i>
                                    <?php echo htmlspecialchars($result[1]); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="config-section">
                    <h2><i class="fas fa-cogs"></i> Initialize Settings Table</h2>
                    <p>This will create the settings table if it doesn't exist and insert default Google Auth configuration entries.</p>
                    
                    <form method="POST">
                        <button type="submit" name="init_settings" class="btn btn-primary">
                            <i class="fas fa-hammer"></i> Initialize Settings Table
                        </button>
                    </form>
                </div>

                <div class="config-section">
                    <h2><i class="fas fa-arrow-right"></i> Next Steps</h2>
                    <ol>
                        <li>Run the initialization above</li>
                        <li><a href="google-auth-settings.php">Configure Google OAuth Settings</a></li>
                        <li><a href="debug-google-auth.php">Use Debug Tool if Issues Persist</a></li>
                    </ol>
                </div>
            </div>
        </main>
    </div>
</body>
</html>