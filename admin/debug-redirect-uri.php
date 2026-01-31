<?php
// Debug Redirect URI Generation
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
    
    // Debug information
    $debug_info = [];
    $debug_info['SITE_URL'] = defined('SITE_URL') ? SITE_URL : 'NOT DEFINED';
    $debug_info['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'NOT SET';
    $debug_info['REQUEST_SCHEME'] = $_SERVER['REQUEST_SCHEME'] ?? 'NOT SET';
    $debug_info['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'NOT SET';
    $debug_info['HTTPS'] = $_SERVER['HTTPS'] ?? 'NOT SET';
    
    // Generate redirect URI using different methods
    $methods = [];
    
    // Method 1: Using SITE_URL constant
    $methods['method1'] = [
        'name' => 'SITE_URL Constant',
        'value' => defined('SITE_URL') ? SITE_URL . '/auth/google/callback.php' : 'SITE_URL not defined'
    ];
    
    // Method 2: Using HTTP_HOST
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $methods['method2'] = [
        'name' => 'HTTP_HOST Server Variable',
        'value' => $protocol . ($_SERVER['HTTP_HOST'] ?? 'unknown') . '/auth/google/callback.php'
    ];
    
    // Method 3: Using SERVER_NAME
    $methods['method3'] = [
        'name' => 'SERVER_NAME Server Variable',
        'value' => $protocol . ($_SERVER['SERVER_NAME'] ?? 'unknown') . '/auth/google/callback.php'
    ];
    
    // Method 4: Manual construction (what should work)
    $methods['method4'] = [
        'name' => 'Manual Construction',
        'value' => 'https://great10.xyz/auth/google/callback.php'
    ];
    
    // Get current configuration
    $current_google = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM settings WHERE setting_key LIKE 'google_%'");
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $current_google[str_replace('google_', '', $row['setting_key'])] = $row['setting_value'];
        }
    } catch (Exception $e) {
        $current_google = ['error' => $e->getMessage()];
    }
    
    $debug_info['current_google_config'] = $current_google;
    
} catch (Exception $e) {
    $fatal_error = $e->getMessage();
    $debug_info['fatal_error'] = $e->getMessage();
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Redirect URI Generation</title>
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
        .method-item { margin: 15px 0; padding: 15px; border-left: 3px solid #00f5ff; }
        .recommended { border-left-color: #00ff9d; background: rgba(0, 255, 157, 0.1); }
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
                <a href="debug-redirect-uri.php" class="nav-item active">
                    <i class="fas fa-link"></i> Debug Redirect URI
                </a>
                <a href="debug-google-auth.php" class="nav-item">
                    <i class="fas fa-bug"></i> Full Debug
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
                <h1><i class="fas fa-link"></i> Redirect URI Debug Tool</h1>
                <p>Troubleshooting Google OAuth redirect URI generation</p>
            </header>

            <div class="admin-content">
                <?php if (isset($fatal_error)): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-triangle"></i> Fatal Error: <?php echo htmlspecialchars($fatal_error); ?>
                    </div>
                <?php endif; ?>

                <div class="debug-section">
                    <h2><i class="fas fa-info-circle"></i> Server Information</h2>
                    <?php foreach ($debug_info as $key => $value): ?>
                        <div class="debug-item">
                            <span class="debug-label"><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</span>
                            <?php if (is_array($value)): ?>
                                <pre class="debug-value"><?php echo htmlspecialchars(print_r($value, true)); ?></pre>
                            <?php else: ?>
                                <span class="debug-value"><?php echo htmlspecialchars($value); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="debug-section">
                    <h2><i class="fas fa-calculator"></i> Redirect URI Generation Methods</h2>
                    <?php foreach ($methods as $key => $method): ?>
                        <div class="method-item <?php echo $key === 'method4' ? 'recommended' : ''; ?>">
                            <h3><?php echo htmlspecialchars($method['name']); ?></h3>
                            <div class="debug-item">
                                <span class="debug-label">Generated URI:</span>
                                <input type="text" value="<?php echo htmlspecialchars($method['value']); ?>" readonly style="width: 100%; padding: 8px; background: #0b1120; color: #f1f5f9; border: 1px solid #334155; border-radius: 4px;">
                            </div>
                            <?php if ($key === 'method4'): ?>
                                <div class="debug-item" style="background: rgba(0, 255, 157, 0.1); border-left: 3px solid #00ff9d;">
                                    <span class="debug-label success">Recommended Method</span>
                                    <span class="debug-value">This is the most reliable approach for your setup</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="debug-section">
                    <h2><i class="fas fa-code"></i> Recommended Solution</h2>
                    <p>Based on your server configuration, here's the recommended redirect URI for Google OAuth:</p>
                    <div class="debug-item">
                        <input type="text" id="recommended-uri" value="https://great10.xyz/auth/google/callback.php" readonly style="width: 100%; padding: 12px; background: #0b1120; color: #00ff9d; border: 2px solid #00ff9d; border-radius: 6px; font-weight: bold; font-size: 1.1em;">
                    </div>
                    <button onclick="copyToClipboard()" class="btn btn-primary" style="margin-top: 10px;">
                        <i class="fas fa-copy"></i> Copy to Clipboard
                    </button>
                </div>

                <div class="debug-section">
                    <h2><i class="fas fa-arrow-right"></i> Next Steps</h2>
                    <ol>
                        <li>Copy the recommended redirect URI above</li>
                        <li>Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                        <li>Navigate to APIs & Services → Credentials</li>
                        <li>Find your OAuth 2.0 Client ID</li>
                        <li>Add the copied URI to "Authorized redirect URIs"</li>
                        <li>Save the changes</li>
                        <li><a href="google-auth-settings.php">Return to Google Auth Settings</a> to configure your credentials</li>
                    </ol>
                </div>
            </div>
        </main>
    </div>

    <script>
        function copyToClipboard() {
            const uriInput = document.getElementById('recommended-uri');
            uriInput.select();
            document.execCommand('copy');
            
            // Show confirmation
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Copied!';
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('btn-success');
            }, 2000);
        }
    </script>
</body>
</html>