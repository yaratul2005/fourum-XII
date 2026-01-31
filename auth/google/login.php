<?php
// Google OAuth Login Initiator
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session will be started in config.php
// session_start(); // Removed to prevent conflict

try {
    require_once '../../config.php';
    require_once '../../includes/functions.php';
    
    // Get Google configuration
    $google_config = get_google_config();
    
    if (!$google_config['enabled'] || empty($google_config['client_id'])) {
        throw new Exception('Google authentication is not enabled or properly configured');
    }
    
    // Store the redirect URL for after authentication
    $redirect_after_login = $_GET['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? '/';
    $_SESSION['login_redirect'] = $redirect_after_login;
    
    // Google OAuth authorization URL
    $auth_url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query([
        'client_id' => $google_config['client_id'],
        'redirect_uri' => $google_config['redirect_uri'] ?: (defined('SITE_URL') ? SITE_URL : 'https://' . $_SERVER['HTTP_HOST']) . '/auth/google/callback.php',
        'scope' => 'openid email profile',
        'response_type' => 'code',
        'access_type' => 'online',
        'prompt' => 'select_account'
    ]);
    
    // Redirect to Google
    header("Location: $auth_url");
    exit();
    
} catch (Exception $e) {
    error_log("Google OAuth initiation error: " . $e->getMessage());
    $_SESSION['error_message'] = $e->getMessage();
    
    // Redirect back to login page with error
    $redirect_url = '/login.php?error=google_auth_unavailable';
    if (strpos($_SERVER['HTTP_REFERER'] ?? '', 'register') !== false) {
        $redirect_url = '/register.php?error=google_auth_unavailable';
    }
    header("Location: $redirect_url");
    exit();
}

function get_google_config() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'google_%'");
        $config = [];
        while ($row = $stmt->fetch()) {
            $config[str_replace('google_', '', $row['setting_key'])] = $row['setting_value'];
        }
        
        // Ensure we have defaults
        $defaults = [
            'enabled' => 0,
            'client_id' => '',
            'client_secret' => '',
            'redirect_uri' => ''
        ];
        
        return array_merge($defaults, $config);
    } catch (Exception $e) {
        error_log("Google config retrieval error: " . $e->getMessage());
        return [
            'enabled' => 0,
            'client_id' => '',
            'client_secret' => '',
            'redirect_uri' => ''
        ];
    }
}
?>