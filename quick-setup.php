<?php
// Quick Database Setup Script - cPanel Version
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Furom Quick Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #007cba; background: #f8f9fa; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .info { border-left-color: #17a2b8; background: #d1ecf1; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        input, select { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #007cba; color: white; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #005a87; }
        code { background: #e9ecef; padding: 2px 5px; border-radius: 3px; }
        .creds { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🚀 Furom Quick Setup</h1>";
echo "<div class='warning'>";
echo "<h3>⚠️ Important cPanel Notes:</h3>";
echo "<ul>";
echo "<li>Create database FIRST through cPanel → MySQL Databases</li>";
echo "<li>Database name will be: <code>yourusername_furom_db</code></li>";
echo "<li>Database user will be: <code>yourusername_furom_user</code></li>";
echo "<li>This script CANNOT create databases (cPanel restriction)</li>";
echo "</ul>";
echo "</div>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = 'localhost'; // Force localhost
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = trim($_POST['db_name'] ?? '');
    
    echo "<div class='step'>";
    echo "<h3>Step 1: Testing Database Connection</h3>";
    
    if (empty($db_user) || empty($db_name)) {
        echo "<div class='error'>❌ Please fill in all required fields</div>";
        echo "</div>";
    } else {
        try {
            // Test connection without database first
            $pdo_test = new PDO("mysql:host=$db_host;charset=utf8", $db_user, $db_pass);
            $pdo_test->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<div class='success'>✅ Connected to MySQL server successfully!</div>";
            
            // Test specific database
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<div class='success'>✅ Connected to database '$db_name' successfully!</div>";
            
            echo "</div>";
            
            echo "<div class='step'>";
            echo "<h3>Step 2: Creating Configuration File</h3>";
            
            // Create config file
            $secret_key = 'sk_' . bin2hex(random_bytes(32));
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
define('SECRET_KEY', '$secret_key');
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
                echo "<div class='success'>✅ Configuration file created successfully!</div>";
            } else {
                echo "<div class='error'>❌ Failed to create config.php - check file permissions</div>";
                echo "<div class='info'><strong>Manual step:</strong> Create a file named <code>config.php</code> with the following content:<br>";
                echo "<pre>" . htmlspecialchars($config_content) . "</pre></div>";
            }
            
            echo "</div>";
            
            echo "<div class='step'>";
            echo "<h3>Step 3: Import Database Structure</h3>";
            
            // Import cPanel-compatible database schema
            $sql = file_get_contents('database/cpanel-schema.sql');
            if ($sql === false) {
                echo "<div class='error'>❌ Could not read database/cpanel-schema.sql file</div>";
                echo "<div class='info'><strong>Alternative:</strong> Import via phpMyAdmin:</div>";
                echo "<ol>";
                echo "<li>Go to phpMyAdmin in cPanel</li>";
                echo "<li>Select your database</li>";
                echo "<li>Click 'Import' tab</li>";
                echo "<li>Choose <code>database/cpanel-schema.sql</code></li>";
                echo "<li>Click 'Go'</li>";
                echo "</ol>";
            } else {
                try {
                    // Split by semicolon and execute each statement
                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                    $success_count = 0;
                    
                    foreach ($statements as $statement) {
                        if (!empty($statement) && 
                            strpos(strtoupper($statement), 'CREATE DATABASE') === false &&
                            strlen($statement) > 10) {
                            $pdo->exec($statement);
                            $success_count++;
                        }
                    }
                    echo "<div class='success'>✅ Database structure imported ($success_count statements executed)</div>";
                } catch(Exception $e) {
                    echo "<div class='error'>❌ Database import failed: " . $e->getMessage() . "</div>";
                    echo "<div class='info'><strong>Manual alternative:</strong> Import <code>database/cpanel-schema.sql</code> via phpMyAdmin</div>";
                }
            }
            
            echo "</div>";
            
            echo "<div class='step'>";
            echo "<h3>Step 4: Create Admin User</h3>";
            
            // Create admin user
            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
            $verification_token = bin2hex(random_bytes(32));
            
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password, verification_token, verified, exp, created_at) VALUES (?, ?, ?, ?, 1, 1000, NOW())");
                if ($stmt->execute(['admin', 'admin@great10.xyz', $admin_password, $verification_token])) {
                    echo "<div class='success'>✅ Admin user created successfully!</div>";
                    echo "<div class='creds'>";
                    echo "<strong>Admin Login Credentials:</strong><br>";
                    echo "Username: <code>admin</code><br>";
                    echo "Password: <code>admin123</code><br>";
                    echo "<small style='color: #666;'>⚠️ Please change this password after first login!</small>";
                    echo "</div>";
                } else {
                    echo "<div class='info'>ℹ️ Admin user may already exist</div>";
                }
            } catch(Exception $e) {
                echo "<div class='error'>❌ Failed to create admin user: " . $e->getMessage() . "</div>";
            }
            
            echo "</div>";
            
            echo "<div class='step success'>";
            echo "<h3>🎉 Setup Complete!</h3>";
            echo "<p>Your Furom installation is ready to use.</p>";
            echo "<div style='margin: 15px 0;'>";
            echo "<a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;'>Go to Forum</a>";
            echo "<a href='login.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Login as Admin</a>";
            echo "</div>";
            echo "<p><strong>Important:</strong> Delete this <code>quick-setup.php</code> file for security!</p>";
            echo "</div>";
            
        } catch(PDOException $e) {
            echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
            echo "<div class='info'>";
            echo "<h4>Troubleshooting:</h4>";
            echo "<ul>";
            echo "<li>Did you create the database through cPanel first?</li>";
            echo "<li>Is the database name exactly as shown in cPanel (with prefix)?</li>";
            echo "<li>Does the database user have ALL privileges?</li>";
            echo "<li>Try the database name without the 'greatxyz_' prefix if that's causing issues</li>";
            echo "</ul>";
            echo "</div>";
            echo "</div>";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !empty($errors)) {
    echo "<div class='step info'>";
    echo "<h3>🔧 Database Setup</h3>";
    echo "<p>Enter your database credentials from cPanel:</p>";
    echo "<form method='POST'>";
    
    echo "<label>Database Username:</label>";
    echo "<input type='text' name='db_user' placeholder='Usually: cpanelusername_furom_user' required>";
    
    echo "<label>Database Password:</label>";
    echo "<input type='password' name='db_pass' required>";
    
    echo "<label>Database Name:</label>";
    echo "<input type='text' name='db_name' placeholder='Usually: cpanelusername_furom_db' required>";
    
    echo "<button type='submit'>Setup Database</button>";
    echo "</form>";
    echo "</div>";
    
    echo "<div class='step info'>";
    echo "<h4>💡 How to find your database details:</h4>";
    echo "<ol>";
    echo "<div class='step info'>";
    echo "<h3>🔧 Database Setup</h3>";
    echo "<p>Enter your database credentials from cPanel:</p>";
    echo "<form method='POST'>";
                
    echo "<label>Database Username:</label>";
    echo "<input type='text' name='db_user' placeholder='Example: greatxyz_furom_user' required>";
                
    echo "<label>Database Password:</label>";
    echo "<input type='password' name='db_pass' required>";
                
    echo "<label>Database Name:</label>";
    echo "<input type='text' name='db_name' placeholder='Example: greatxyz_furom_db' required>";
                
    echo "<button type='submit'>Setup Database</button>";
    echo "</form>";
    echo "</div>";
                
    echo "<div class='step info'>";
    echo "<h4>📋 cPanel Setup Instructions:</h4>";
    echo "<ol>";
    echo "<li><strong>Login to cPanel:</strong> https://great10.xyz/cpanel</li>";
    echo "<li><strong>Create Database:</strong> MySQL Databases → Create 'furom_db'</li>";
    echo "<li><strong>Create User:</strong> Create 'furom_user' with strong password</li>";
    echo "<li><strong>Add User to Database:</strong> Grant ALL privileges</li>";
    echo "<li><strong>Get Full Names:</strong> Note the prefixed names (greatxyz_furom_db)</li>";
    echo "<li><strong>Come back here:</strong> Enter the exact names from cPanel</li>";
    echo "</ol>";
    echo "<p><strong>Important:</strong> The database names in cPanel include your account prefix!</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>