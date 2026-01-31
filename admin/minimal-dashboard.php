<?php
// Start with minimal setup to avoid 500 errors
ob_start();

// Basic error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Try to include config
$config_loaded = false;
$functions_loaded = false;

try {
    if (file_exists('../config.php')) {
        require_once '../config.php';
        $config_loaded = true;
    }
} catch (Exception $e) {
    $config_error = $e->getMessage();
}

try {
    if (file_exists('../includes/functions.php')) {
        require_once '../includes/functions.php';
        $functions_loaded = true;
    }
} catch (Exception $e) {
    $functions_error = $e->getMessage();
}

// Check if we can establish database connection
$db_working = false;
try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT 1");
        if ($stmt) {
            $db_working = true;
        }
    }
} catch (Exception $e) {
    $db_error = $e->getMessage();
}

// Check admin access
$admin_access = false;
try {
    if (function_exists('is_logged_in') && is_logged_in()) {
        $current_user = get_user_data(get_current_user_id());
        if ($current_user && $current_user['username'] === 'admin') {
            $admin_access = true;
        }
    }
} catch (Exception $e) {
    $admin_error = $e->getMessage();
}

// If we don't have proper access, redirect or show error
if (!$admin_access) {
    if ($config_loaded && $functions_loaded && function_exists('is_logged_in')) {
        // User is logged in but not admin
        if (is_logged_in()) {
            header('Location: ../index.php');
        } else {
            header('Location: ../login.php');
        }
    } else {
        // Something fundamental is broken
        die("System configuration error. Please check server logs.");
    }
    exit();
}

// If we get here, we have admin access, so show minimal dashboard
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minimal Admin Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #333; color: white; padding: 15px; margin: -20px -20px 20px -20px; border-radius: 8px 8px 0 0; }
        .nav { display: flex; gap: 15px; margin-bottom: 20px; }
        .nav a { padding: 10px 15px; background: #007cba; color: white; text-decoration: none; border-radius: 4px; }
        .nav a:hover { background: #005a87; }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Minimal Admin Dashboard</h1>
            <p>Furom V3 Administration Panel - Debug Mode</p>
        </div>

        <div class="nav">
            <a href="dashboard.php">Full Dashboard</a>
            <a href="users.php">Users</a>
            <a href="posts.php">Posts</a>
            <a href="reports.php">Reports</a>
            <a href="settings.php">Settings</a>
            <a href="backup.php">Backup</a>
            <a href="../index.php">Main Site</a>
        </div>

        <h2>System Status</h2>
        
        <?php if ($config_loaded): ?>
            <div class="status success">✅ Configuration loaded successfully</div>
        <?php else: ?>
            <div class="status error">❌ Configuration failed to load<?php if (isset($config_error)) echo ": " . htmlspecialchars($config_error); ?></div>
        <?php endif; ?>

        <?php if ($functions_loaded): ?>
            <div class="status success">✅ Functions loaded successfully</div>
        <?php else: ?>
            <div class="status error">❌ Functions failed to load<?php if (isset($functions_error)) echo ": " . htmlspecialchars($functions_error); ?></div>
        <?php endif; ?>

        <?php if ($db_working): ?>
            <div class="status success">✅ Database connection working</div>
        <?php else: ?>
            <div class="status error">❌ Database connection failed<?php if (isset($db_error)) echo ": " . htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <?php if ($admin_access): ?>
            <div class="status success">✅ Admin access granted</div>
        <?php else: ?>
            <div class="status error">❌ Admin access denied<?php if (isset($admin_error)) echo ": " . htmlspecialchars($admin_error); ?></div>
        <?php endif; ?>

        <h2>Quick Actions</h2>
        <div class="status info">
            <p><strong>Server Information:</strong></p>
            <ul>
                <li>PHP Version: <?php echo PHP_VERSION; ?></li>
                <li>Server Software: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></li>
                <li>Current Time: <?php echo date('Y-m-d H:i:s'); ?></li>
            </ul>
        </div>

        <h2>Next Steps</h2>
        <ol>
            <li>If all statuses above are green, try the <a href="dashboard.php">Full Dashboard</a></li>
            <li>If there are red errors, check your server configuration</li>
            <li>Review server error logs for more detailed information</li>
            <li>Ensure database credentials in config.php are correct</li>
        </ol>

        <div class="status info">
            <p><strong>Troubleshooting Tips:</strong></p>
            <ul>
                <li>Check file permissions (should be 644 for files, 755 for directories)</li>
                <li>Verify database connection settings in config.php</li>
                <li>Ensure all required PHP extensions are installed</li>
                <li>Check server error logs for specific error messages</li>
            </ul>
        </div>
    </div>
</body>
</html>