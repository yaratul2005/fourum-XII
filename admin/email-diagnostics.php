<?php
// Email Diagnostics and Testing Tool
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
if ($current_user['username'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$message = '';
$message_type = '';
$test_results = [];

// Handle test email
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

function send_verification_email_test($email, $token, $method = 'basic', $smtp_settings = null) {
    try {
        $subject = "Furom Email Test";
        $verification_link = SITE_URL . "/verify.php?token=" . $token;
        
        $message = "
        <html>
        <head>
            <title>Furom Email Test</title>
        </head>
        <body style='font-family: Arial, sans-serif; background: #0a0a1a; color: #f0f0f0; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: #121225; border-radius: 10px; padding: 30px;'>
                <h1 style='color: #00f5ff; text-align: center;'><i class='fas fa-robot'></i> FUROM EMAIL TEST</h1>
                <div style='text-align: center; margin: 20px 0;'>
                    <p>This is a test email from your Furom installation.</p>
                    <p><strong>Test Method:</strong> " . strtoupper($method) . "</p>
                    <p><strong>Time Sent:</strong> " . date('Y-m-d H:i:s') . "</p>
                    <p><strong>Server:</strong> " . $_SERVER['SERVER_NAME'] . "</p>
                </div>
                <div style='background: rgba(0,245,255,0.1); padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p><strong>Verification Link (for testing):</strong></p>
                    <p style='word-break: break-all;'><a href='$verification_link' style='color: #00f5ff;'>$verification_link</a></p>
                </div>
                <p style='text-align: center; color: #888; font-size: 0.9em;'>
                    If you received this email, your email configuration is working!
                </p>
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
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'details' => 'Error: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

function test_server_configuration() {
    $results = [];
    
    // Check if mail function exists
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
    
    return $results;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Diagnostics - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
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
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1><i class="fas fa-envelope"></i> Email Diagnostics</h1>
                <p>Test and troubleshoot email sending functionality</p>
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
                                   value="<?php echo htmlspecialchars(defined('ADMIN_EMAIL') ? ADMIN_EMAIL : ''); ?>"
                                   placeholder="test@example.com" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-primary);">
                        </div>
                        
                        <button type="submit" name="test_email" class="btn btn-primary" style="margin-top: 15px;">
                            <i class="fas fa-paper-plane"></i> Send Test Email
                        </button>
                    </form>
                </div>
                
                <?php if (!empty($test_results)): ?>
                    <div class="test-results">
                        <h3><i class="fas fa-chart-bar"></i> Test Results</h3>
                        
                        <?php foreach ($test_results as $test_key => $test_data): ?>
                            <div class="test-result-item result-<?php echo strtolower($test_data['result']['success'] ? 'pass' : ($test_data['result']['status'] ?? 'fail')); ?>">
                                <h4>
                                    <span class="status-indicator status-<?php echo strtolower($test_data['result']['success'] ? 'pass' : ($test_data['result']['status'] ?? 'fail')); ?>"></span>
                                    <?php echo htmlspecialchars($test_data['name']); ?>
                                </h4>
                                <p><strong>Description:</strong> <?php echo htmlspecialchars($test_data['description']); ?></p>
                                <p><strong>Result:</strong> <?php echo htmlspecialchars($test_data['result']['details']); ?></p>
                                <?php if (isset($test_data['result']['timestamp'])): ?>
                                    <p><strong>Timestamp:</strong> <?php echo htmlspecialchars($test_data['result']['timestamp']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (isset($test_data['result']) && is_array($test_data['result']) && isset($test_data['result'][0])): ?>
                                    <?php foreach ($test_data['result'] as $sub_result): ?>
                                        <div style="margin-left: 20px; margin-top: 10px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 5px;">
                                            <strong><?php echo htmlspecialchars($sub_result['name']); ?>:</strong> 
                                            <span class="<?php echo 'status-' . strtolower($sub_result['status']); ?>">
                                                <?php echo htmlspecialchars($sub_result['status']); ?>
                                            </span>
                                            <br>
                                            <small><?php echo htmlspecialchars($sub_result['details']); ?></small>
                                        </div>
                                    <?php endforeach; ?>
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