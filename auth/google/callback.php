<?php
// Google OAuth Callback Handler
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

try {
    require_once '../../config.php';
    require_once '../../includes/functions.php';
    
    // Get Google configuration
    $google_config = get_google_config();
    
    if (!$google_config['enabled'] || empty($google_config['client_id']) || empty($google_config['client_secret'])) {
        throw new Exception('Google authentication is not properly configured');
    }
    
    // Check for authorization code
    if (!isset($_GET['code'])) {
        if (isset($_GET['error'])) {
            throw new Exception('Google authentication failed: ' . $_GET['error']);
        }
        throw new Exception('No authorization code received');
    }
    
    $auth_code = $_GET['code'];
    
    // Exchange authorization code for access token
    $token_url = 'https://oauth2.googleapis.com/token';
    $token_data = [
        'code' => $auth_code,
        'client_id' => $google_config['client_id'],
        'client_secret' => $google_config['client_secret'],
        'redirect_uri' => $google_config['redirect_uri'] ?: (defined('SITE_URL') ? SITE_URL : 'https://' . $_SERVER['HTTP_HOST']) . '/auth/google/callback.php',
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $token_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        throw new Exception('Failed to exchange authorization code for token');
    }
    
    $token_info = json_decode($token_response, true);
    if (!$token_info || !isset($token_info['access_token'])) {
        throw new Exception('Invalid token response from Google');
    }
    
    // Get user info from Google
    $access_token = $token_info['access_token'];
    $user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $user_info_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $user_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        throw new Exception('Failed to retrieve user information from Google');
    }
    
    $user_info = json_decode($user_response, true);
    if (!$user_info || !isset($user_info['email'])) {
        throw new Exception('Invalid user information from Google');
    }
    
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$user_info['email']]);
    $existing_user = $stmt->fetch();
    
    if ($existing_user) {
        // User exists, log them in
        $_SESSION['user_id'] = $existing_user['id'];
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$existing_user['id']]);
        
        // Redirect to original destination or home
        $redirect_url = $_SESSION['login_redirect'] ?? '/';
        unset($_SESSION['login_redirect']);
        header("Location: $redirect_url");
        exit();
    } else {
        // New user, create account
        $username = generate_unique_username($user_info['name'] ?? 'google_user');
        $password = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $verification_token = bin2hex(random_bytes(32));
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, verification_token, verified, avatar, exp, created_at) VALUES (?, ?, ?, ?, 1, ?, 10, NOW())");
        $stmt->execute([
            $username,
            $user_info['email'],
            $password,
            $verification_token,
            $user_info['picture'] ?? null
        ]);
        
        $user_id = $pdo->lastInsertId();
        $_SESSION['user_id'] = $user_id;
        
        // Redirect to profile setup
        header("Location: /profile-setup.php?new_user=1");
        exit();
    }
    
} catch (Exception $e) {
    error_log("Google OAuth error: " . $e->getMessage());
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: /login.php?error=google_auth_failed');
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
        return $config;
    } catch (Exception $e) {
        return ['enabled' => 0, 'client_id' => '', 'client_secret' => '', 'redirect_uri' => ''];
    }
}

function generate_unique_username($base_name) {
    global $pdo;
    $base_name = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($base_name));
    if (empty($base_name)) {
        $base_name = 'user' . rand(1000, 9999);
    }
    
    $username = $base_name;
    $counter = 1;
    
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if (!$stmt->fetch()) {
            break;
        }
        $username = $base_name . $counter;
        $counter++;
    }
    
    return $username;
}
?>