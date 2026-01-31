<?php
// Comprehensive Email Testing Tool
require_once 'config.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    header('Location: login.php');
    exit();
}

$message = '';
$message_type = '';
$test_results = [];

// Handle email sending test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    try {
        $test_email = trim($_POST['test_email_address']);
        if (empty($test_email) || !filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please provide a valid email address');
        }
        
        // Generate test token
        $test_token = bin2hex(random_bytes(32));
        
        // Test basic mail function
        $test_results['basic_mail'] = [
            'name' => 'Basic PHP mail()',
            'result' => send_verification_email_test($test_email, $test_token, 'basic'),
            'description' => 'Tests the standard PHP mail() function'
        ];
        
        // Test with current SMTP settings
        $smtp_settings = get_smtp_settings();
        if ($smtp_settings && $smtp_settings['enabled']) {
            $test_results['smtp_mail'] = [
                'name' => 'Configured SMTP',
                'result' => send_verification_email_test($test_email, $test_token, 'smtp', $smtp_settings),
                'description' => 'Tests with your configured SMTP settings'
            ];
        }
        
        // Test server configuration
        $test_results['server_config'] = [
            'name' => 'Server Configuration',
            'result' => test_server_configuration(),
            'description' => 'Checks server mail configuration'
        ];
        
        $message = 'Email tests completed. Check results below.';
        $message_type = 'info';
        
    } catch (Exception $e) {
        $message = 'Error running tests: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle registration simulation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simulate_registration'])) {
    try {
        $test_email = trim($_POST['registration_email']);
        $test_username = trim($_POST['test_username']);
        
        if (empty($test_email) || !filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please provide a valid email address');
        }
        
        if (empty($test_username)) {
            throw new Exception('Please provide a username');
        }
        
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$test_username, $test_email]);
        if ($stmt->fetch()) {
            throw new Exception('Username or email already exists');
        }
        
        // Simulate registration process
        $verification_token = generate_token();
        $hashed_password = password_hash('testpassword123', PASSWORD_DEFAULT);
        
        // Insert test user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, verification_token, created_at) VALUES (?, ?, ?, ?, NOW())");
        $insert_success = $stmt->execute([$test_username, $test_email, $hashed_password, $verification_token]);
        
        if ($insert_success) {
            // Test email sending
            $email_sent = send_verification_email($test_email, $verification_token);
            
            $test_results['registration_simulation'] = [
                'name' => 'Registration Simulation',
                'result' => [
                    'success' => true,
                    'details' => $email_sent ? 'Registration simulated successfully with email sent' : 'Registration simulated but email failed',
                    'user_created' => true,
                    'email_sent' => $email_sent,
                    'test_username' => $test_username,
                    'test_email' => $test_email
                ],
                'description' => 'Complete registration workflow simulation'
            ];
            
            $message = 'Registration simulation completed. Check results below.';
            $message_type = 'info';
        } else {
            throw new Exception('Failed to create test user');
        }
        
    } catch (Exception $e) {
        $message = 'Registration simulation failed: ' . $e->getMessage();
        $message_type = 'error';
    }
}

function send_verification_email_test($email, $token, $method = 'basic', $smtp_settings = null) {
    try {
        $subject = "Furom Email Test";
        $verification_link = SITE_URL . "/verify.php?token=" . $token;
        
        $message = "
        <html>
        <head>
            <title>Furom Email Test</title>
            <style>
                body { font-family: Arial, sans-serif; background: #0a0a1a; color: #f0f0f0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #121225; border-radius: 10px; padding: 30px; }
                .header { text-align: center; margin-bottom: 30px; }
                .header h1 { color: #00f5ff; margin: 0; }
                .content { line-height: 1.6; }
                .info-box { background: rgba(0,245,255,0.1); padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { margin-top: 30px; font-size: 0.9em; color: #888; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1><i class='fas fa-robot'></i> FUROM EMAIL TEST</h1>
                    <h2>System Configuration Test</h2>
                </div>
                <div class='content'>
                    <p>This is a test email from your Furom installation.</p>
                    <div class='info-box'>
                        <p><strong>Test Method:</strong> " . strtoupper($method) . "</p>
                        <p><strong>Time Sent:</strong> " . date('Y-m-d H:i:s') . "</p>
                        <p><strong>Server:</strong> " . $_SERVER['SERVER_NAME'] . "</p>
                        <p><strong>Site URL:</strong> " . SITE_URL . "</p>
                    </div>
                    <div class='info-box'>
                        <p><strong>Verification Link (for testing):</strong></p>
                        <p style='word-break: break-all;'><a href='$verification_link' style='color: #00f5ff;'>$verification_link</a></p>
                    </div>
                    <p style='text-align: center; color: #888; font-size: 0.9em;'>
                        If you received this email, your email configuration is working correctly!
                    </p>
                </div>
                <div class='footer'>
                    <p>Furom Email Testing System</p>
                    <p>&copy; " . date('Y') . " Furom. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . (defined('SMTP_USERNAME') ? SMTP_USERNAME : 'noreply@' . $_SERVER['SERVER_NAME']) . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        if ($method === 'smtp' && $smtp_settings) {
            // Add SMTP authentication headers if needed
            if (!empty($smtp_settings['username']) && !empty($smtp_settings['password'])) {
                $headers .= "Authorization: Basic " . base64_encode($smtp_settings['username'] . ':' . $smtp_settings['password']) . "\r\n";
            }
        }
        
        $result = mail($email, $subject, $message, $headers);
        return [
            'success' => $result,
            'details' => $result ? 'Email sent successfully' : 'Failed to send email',
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $method
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'details' => 'Error: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $method
        ];
    }
}

function test_server_configuration() {
    $results = [];
    
    // Check if mail function is available
    $results['mail_function'] = [
        'name' => 'mail() function available',
        'status' => function_exists('mail') ? 'PASS' : 'FAIL',
        'details' => function_exists('mail') ? 'mail() function is available' : 'mail() function is not available'
    ];
    
    // Check sendmail path
    $sendmail_path = ini_get('sendmail_path');
    $results['sendmail_path'] = [
        'name' => 'Sendmail path',
        'status' => !empty($sendmail_path) ? 'INFO' : 'WARNING',
        'details' => $sendmail_path ?: 'Sendmail path not configured'
    ];
    
    // Check SMTP settings
    $smtp_settings = [
        'SMTP' => ini_get('SMTP'),
        'smtp_port' => ini_get('smtp_port'),
        'sendmail_from' => ini_get('sendmail_from')
    ];
    
    $results['smtp_config'] = [
        'name' => 'SMTP Configuration',
        'status' => (!empty($smtp_settings['SMTP']) || !empty($smtp_settings['sendmail_from'])) ? 'INFO' : 'WARNING',
        'details' => 'SMTP: ' . ($smtp_settings['SMTP'] ?: 'Not set') . ', Port: ' . ($smtp_settings['smtp_port'] ?: 'Not set') . ', From: ' . ($smtp_settings['sendmail_from'] ?: 'Not set')
    ];
    
    return [
        'success' => true,
        'details' => 'Server configuration checked',
        'configuration' => $results,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

function generate_token() {
    return bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Testing Tool - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-results {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid var(--border-color);
        }
        
        .test-result-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid;
        }
        
        .result-pass {
            background: rgba(16, 185, 129, 0.1);
            border-left-color: #10b981;
        }
        
        .result-fail {
            background: rgba(239, 68, 68, 0.1);
            border-left-color: #ef4444;
        }
        
        .result-warning {
            background: rgba(245, 158, 11, 0.1);
            border-left-color: #f59e0b;
        }
        
        .result-info {
            background: rgba(59, 130, 246, 0.1);
            border-left-color: #3b82f6;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .status-pass { background: #10b981; }
        .status-fail { background: #ef4444; }
        .status-warning { background: #f59e0b; }
        .status-info { background: #3b82f6; }
        
        .test-form {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 25px;
            margin: 20px 0;
            border: 1px solid var(--border-color);
        }
        
        .config-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .simulation-form {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 8px;
            padding: 25px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'admin/sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1><i class="fas fa-envelope"></i> Email Testing & Verification</h1>
                <p>Comprehensive email system testing and registration verification</p>
            </header>
            
            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="config-info">
                    <h3><i class="fas fa-info-circle"></i> Current Email Configuration</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
                        <div>
                            <strong>Site URL:</strong><br>
                            <?php echo htmlspecialchars(SITE_URL); ?>
                        </div>
                        <div>
                            <strong>Admin Email:</strong><br>
                            <?php echo htmlspecialchars(defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'Not defined'); ?>
                        </div>
                        <div>
                            <strong>SMTP Username:</strong><br>
                            <?php echo htmlspecialchars(defined('SMTP_USERNAME') ? SMTP_USERNAME : 'Not defined'); ?>
                        </div>
                        <div>
                            <strong>Server Name:</strong><br>
                            <?php echo htmlspecialchars($_SERVER['SERVER_NAME']); ?>
                        </div>
                    </div>
                </div>
                
                <div class="test-form">
                    <h3><i class="fas fa-flask"></i> Send Test Email</h3>
                    <p>Enter an email address to send a test verification email and check your configuration.</p>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="test_email_address">Test Email Address *</label>
                            <input type="email" id="test_email_address" name="test_email_address" 
                                   class="form-control" required placeholder="test@example.com">
                        </div>
                        
                        <button type="submit" name="test_email" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Test Email
                        </button>
                    </form>
                </div>
                
                <div class="simulation-form">
                    <h3><i class="fas fa-user-plus"></i> Registration Simulation</h3>
                    <p>Simulate the complete registration process including email sending.</p>
                    
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="test_username">Test Username *</label>
                                <input type="text" id="test_username" name="test_username" 
                                       class="form-control" required placeholder="testuser123">
                            </div>
                            
                            <div class="form-group">
                                <label for="registration_email">Test Email *</label>
                                <input type="email" id="registration_email" name="registration_email" 
                                       class="form-control" required placeholder="test@example.com">
                            </div>
                        </div>
                        
                        <button type="submit" name="simulate_registration" class="btn btn-success">
                            <i class="fas fa-play"></i> Run Registration Simulation
                        </button>
                    </form>
                </div>
                
                <?php if (!empty($test_results)): ?>
                    <div class="test-results">
                        <h3><i class="fas fa-chart-bar"></i> Test Results</h3>
                        
                        <?php foreach ($test_results as $test_name => $result): ?>
                            <div class="test-result-item <?php 
                                echo $result['result']['success'] ? 'result-pass' : 
                                     (isset($result['result']['status']) && $result['result']['status'] === 'WARNING' ? 'result-warning' : 'result-fail'); 
                            ?>">
                                <h4>
                                    <span class="status-indicator <?php 
                                        echo $result['result']['success'] ? 'status-pass' : 
                                             (isset($result['result']['status']) && $result['result']['status'] === 'WARNING' ? 'status-warning' : 'status-fail'); 
                                    ?>"></span>
                                    <?php echo htmlspecialchars($result['name']); ?>
                                </h4>
                                <p><strong>Description:</strong> <?php echo htmlspecialchars($result['description']); ?></p>
                                
                                <?php if (isset($result['result']['details'])): ?>
                                    <p><strong>Result:</strong> <?php echo htmlspecialchars($result['result']['details']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (isset($result['result']['timestamp'])): ?>
                                    <p><strong>Timestamp:</strong> <?php echo htmlspecialchars($result['result']['timestamp']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (isset($result['result']['method'])): ?>
                                    <p><strong>Method:</strong> <?php echo htmlspecialchars(strtoupper($result['result']['method'])); ?></p>
                                <?php endif; ?>
                                
                                <?php if (isset($result['result']['email_sent'])): ?>
                                    <p><strong>Email Sent:</strong> 
                                        <span style="color: <?php echo $result['result']['email_sent'] ? '#10b981' : '#ef4444'; ?>">
                                            <?php echo $result['result']['email_sent'] ? 'YES' : 'NO'; ?>
                                        </span>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if (isset($result['result']['user_created'])): ?>
                                    <p><strong>User Created:</strong> 
                                        <span style="color: <?php echo $result['result']['user_created'] ? '#10b981' : '#ef4444'; ?>">
                                            <?php echo $result['result']['user_created'] ? 'YES' : 'NO'; ?>
                                        </span>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if (isset($result['result']['configuration'])): ?>
                                    <div style="margin-top: 15px;">
                                        <h5>Configuration Details:</h5>
                                        <?php foreach ($result['result']['configuration'] as $config_item): ?>
                                            <div style="padding: 8px; margin: 5px 0; border-radius: 4px; background: rgba(255,255,255,0.05);">
                                                <strong><?php echo htmlspecialchars($config_item['name']); ?>:</strong>
                                                <span style="margin-left: 10px; padding: 2px 8px; border-radius: 3px; background: 
                                                    <?php echo $config_item['status'] === 'PASS' ? 'rgba(16, 185, 129, 0.2)' : 
                                                          ($config_item['status'] === 'FAIL' ? 'rgba(239, 68, 68, 0.2)' : 'rgba(245, 158, 11, 0.2)'); ?>">
                                                    <?php echo htmlspecialchars($config_item['status']); ?>
                                                </span>
                                                <br>
                                                <small><?php echo htmlspecialchars($config_item['details']); ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="test-results">
                    <h3><i class="fas fa-cogs"></i> Troubleshooting Tips</h3>
                    <div style="line-height: 1.6;">
                        <h4>Common Issues and Solutions:</h4>
                        <ul>
                            <li><strong>Mail not sending:</strong> Check server mail configuration and PHP mail settings</li>
                            <li><strong>SPAM folder:</strong> Test emails often go to spam - check spam/junk folders</li>
                            <li><strong>SMTP authentication:</strong> Ensure SMTP credentials are correct in admin settings</li>
                            <li><strong>Firewall blocking:</strong> Some hosts block outgoing SMTP connections</li>
                            <li><strong>DNS issues:</strong> Verify domain DNS records are properly configured</li>
                        </ul>
                        
                        <h4>Recommended Actions:</h4>
                        <ol>
                            <li>Test with multiple email providers (Gmail, Outlook, etc.)</li>
                            <li>Check server error logs for mail-related errors</li>
                            <li>Verify SPF and DKIM records for your domain</li>
                            <li>Consider using a dedicated SMTP service like SendGrid or Amazon SES</li>
                            <li>Test both basic mail() and SMTP configurations</li>
                        </ol>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>