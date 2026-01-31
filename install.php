<?php
// Furom Installation Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$messages = [];

// Check requirements
function checkRequirements() {
    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'GD Library' => extension_loaded('gd'),
        'OpenSSL Extension' => extension_loaded('openssl')
    ];
    
    return $requirements;
}

// Test database connection
function testDatabaseConnection($host, $user, $pass, $name) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return ['success' => true, 'pdo' => $pdo];
    } catch(PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Execute SQL file
function executeSQLFile($pdo, $filepath) {
    try {
        $sql = file_get_contents($filepath);
        if ($sql === false) {
            return ['success' => false, 'error' => 'Could not read SQL file'];
        }
        
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        return ['success' => true];
    } catch(Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch($step) {
        case 2: // Database configuration
            $db_host = $_POST['db_host'] ?? 'localhost';
            $db_user = $_POST['db_user'] ?? '';
            $db_pass = $_POST['db_pass'] ?? '';
            $db_name = $_POST['db_name'] ?? '';
            
            $test_result = testDatabaseConnection($db_host, $db_user, $db_pass, $db_name);
            
            if ($test_result['success']) {
                // Create config file
                $config_content = "<?php
// Database Configuration
define('DB_HOST', '$db_host');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('DB_NAME', '$db_name');

// Site Configuration
define('SITE_URL', 'https://' . \$_SERVER['HTTP_HOST']);
define('SITE_NAME', 'Furom - Futuristic Forum');
define('ADMIN_EMAIL', 'admin@' . \$_SERVER['HTTP_HOST']);

// Security Settings
define('SECRET_KEY', '" . bin2hex(random_bytes(32)) . "');
define('SESSION_TIMEOUT', 3600);

// Experience Points Configuration
define('EXP_POST', 10);
define('EXP_COMMENT', 5);
define('EXP_UPVOTE', 2);
define('EXP_DOWNVOTE', -1);

// Email Configuration
define('SMTP_HOST', 'mail.' . \$_SERVER['HTTP_HOST']);
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@' . \$_SERVER['HTTP_HOST']);
define('SMTP_PASSWORD', 'your_email_password');

// Initialize session
session_start();

// Database connection
try {
    \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8\", DB_USER, DB_PASS);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException \$e) {
    die(\"Connection failed: \" . \$e->getMessage());
}

// Autoload functions
require_once 'includes/functions.php';
?>
";
                
                if (file_put_contents('config.php', $config_content)) {
                    $messages[] = ['type' => 'success', 'text' => 'Configuration file created successfully'];
                    $step = 3;
                } else {
                    $messages[] = ['type' => 'error', 'text' => 'Failed to create configuration file. Check file permissions.'];
                }
            } else {
                $messages[] = ['type' => 'error', 'text' => 'Database connection failed: ' . $test_result['error']];
            }
            break;
            
        case 3: // Import database
            require_once 'config.php';
            $import_result = executeSQLFile($pdo, 'database/schema.sql');
            
            if ($import_result['success']) {
                $messages[] = ['type' => 'success', 'text' => 'Database imported successfully'];
                $step = 4;
            } else {
                $messages[] = ['type' => 'error', 'text' => 'Database import failed: ' . $import_result['error']];
            }
            break;
            
        case 4: // Create admin user
            $admin_username = $_POST['admin_username'] ?? '';
            $admin_email = $_POST['admin_email'] ?? '';
            $admin_password = $_POST['admin_password'] ?? '';
            
            if ($admin_username && $admin_email && $admin_password) {
                $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                $verification_token = bin2hex(random_bytes(32));
                
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, verification_token, verified, exp, created_at) VALUES (?, ?, ?, ?, 1, 1000, NOW())");
                
                if ($stmt->execute([$admin_username, $admin_email, $hashed_password, $verification_token])) {
                    $messages[] = ['type' => 'success', 'text' => 'Admin user created successfully'];
                    $step = 5;
                } else {
                    $messages[] = ['type' => 'error', 'text' => 'Failed to create admin user'];
                }
            } else {
                $messages[] = ['type' => 'error', 'text' => 'Please fill all admin user fields'];
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Furom Installation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #00f5ff 0%, #ff00ff 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .progress-bar {
            display: flex;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            overflow: hidden;
            margin: 1rem 0;
        }
        
        .progress-step {
            flex: 1;
            padding: 1rem;
            text-align: center;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .progress-step.active {
            background: rgba(255,255,255,0.3);
        }
        
        .progress-step.completed {
            background: rgba(0,255,157,0.3);
        }
        
        .content {
            padding: 2rem;
        }
        
        .step {
            display: none;
        }
        
        .step.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #00f5ff;
        }
        
        .btn {
            background: linear-gradient(135deg, #00f5ff 0%, #ff00ff 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .message.success {
            background: rgba(0,255,157,0.1);
            border: 1px solid #00ff9d;
            color: #00b36e;
        }
        
        .message.error {
            background: rgba(255,71,87,0.1);
            border: 1px solid #ff4757;
            color: #cc3333;
        }
        
        .requirements {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .requirement-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .requirement-item:last-child {
            border-bottom: none;
        }
        
        .status {
            font-weight: 500;
        }
        
        .status.pass {
            color: #28a745;
        }
        
        .status.fail {
            color: #dc3545;
        }
        
        .next-steps {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 1.5rem;
            border-radius: 0 8px 8px 0;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Furom Installation</h1>
            <p>Futuristic Forum Platform Setup</p>
            
            <div class="progress-bar">
                <div class="progress-step <?php echo $step >= 1 ? 'completed' : ''; ?> <?php echo $step == 1 ? 'active' : ''; ?>">1. Requirements</div>
                <div class="progress-step <?php echo $step >= 2 ? 'completed' : ''; ?> <?php echo $step == 2 ? 'active' : ''; ?>">2. Database</div>
                <div class="progress-step <?php echo $step >= 3 ? 'completed' : ''; ?> <?php echo $step == 3 ? 'active' : ''; ?>">3. Import Data</div>
                <div class="progress-step <?php echo $step >= 4 ? 'completed' : ''; ?> <?php echo $step == 4 ? 'active' : ''; ?>">4. Admin User</div>
                <div class="progress-step <?php echo $step >= 5 ? 'completed' : ''; ?> <?php echo $step == 5 ? 'active' : ''; ?>">5. Complete</div>
            </div>
        </div>
        
        <div class="content">
            <?php foreach($messages as $msg): ?>
                <div class="message <?php echo $msg['type']; ?>">
                    <?php echo htmlspecialchars($msg['text']); ?>
                </div>
            <?php endforeach; ?>
            
            <!-- Step 1: Requirements Check -->
            <div class="step <?php echo $step == 1 ? 'active' : ''; ?>">
                <h2>System Requirements Check</h2>
                <p>Please ensure your server meets the following requirements:</p>
                
                <div class="requirements">
                    <?php $requirements = checkRequirements(); ?>
                    <?php foreach($requirements as $req => $passed): ?>
                        <div class="requirement-item">
                            <span><?php echo $req; ?></span>
                            <span class="status <?php echo $passed ? 'pass' : 'fail'; ?>">
                                <?php echo $passed ? '✓ Pass' : '✗ Fail'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if(array_filter($requirements) === $requirements): ?>
                    <p style="color: #28a745; font-weight: 500;">✅ All requirements met!</p>
                    <a href="?step=2" class="btn">Continue to Database Setup</a>
                <?php else: ?>
                    <p style="color: #dc3545;">⚠️ Some requirements are not met. Please check with your hosting provider.</p>
                <?php endif; ?>
            </div>
            
            <!-- Step 2: Database Configuration -->
            <div class="step <?php echo $step == 2 ? 'active' : ''; ?>">
                <h2>Database Configuration</h2>
                <p>Enter your database connection details:</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="db_host">Database Host</label>
                        <input type="text" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">Database Name</label>
                        <input type="text" id="db_name" name="db_name" value="furom_db" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_user">Database Username</label>
                        <input type="text" id="db_user" name="db_user" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_pass">Database Password</label>
                        <input type="password" id="db_pass" name="db_pass">
                    </div>
                    
                    <button type="submit" class="btn">Test Connection & Continue</button>
                </form>
            </div>
            
            <!-- Step 3: Import Database -->
            <div class="step <?php echo $step == 3 ? 'active' : ''; ?>">
                <h2>Import Database Structure</h2>
                <p>Click below to import the required database tables and initial data:</p>
                
                <form method="POST">
                    <button type="submit" class="btn">Import Database</button>
                </form>
            </div>
            
            <!-- Step 4: Admin User -->
            <div class="step <?php echo $step == 4 ? 'active' : ''; ?>">
                <h2>Create Administrator Account</h2>
                <p>Create your first administrator user account:</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="admin_username">Admin Username</label>
                        <input type="text" id="admin_username" name="admin_username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_email">Admin Email</label>
                        <input type="email" id="admin_email" name="admin_email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_password">Admin Password</label>
                        <input type="password" id="admin_password" name="admin_password" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn">Create Admin User</button>
                </form>
            </div>
            
            <!-- Step 5: Complete -->
            <div class="step <?php echo $step == 5 ? 'active' : ''; ?>">
                <h2 style="color: #28a745;">🎉 Installation Complete!</h2>
                <p>Your Furom forum is now ready to use!</p>
                
                <div class="next-steps">
                    <h3>Next Steps:</h3>
                    <ul style="margin: 1rem 0; padding-left: 1.5rem;">
                        <li>Delete the <code>install.php</code> file for security</li>
                        <li>Visit your forum at: <a href="./" style="color: #2196f3;"><?php echo 'https://' . $_SERVER['HTTP_HOST']; ?></a></li>
                        <li>Login with your admin credentials</li>
                        <li>Customize settings in <code>config.php</code></li>
                        <li>Configure email settings for user verification</li>
                    </ul>
                </div>
                
                <a href="./" class="btn" style="margin-top: 1rem;">Go to Your Forum</a>
            </div>
        </div>
    </div>
</body>
</html>