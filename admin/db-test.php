<?php
// Database Connection Test for cPanel Setup
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>📊 Database Connection Test</h1>";
echo "<p>Testing cPanel database configuration...</p>";

// Test 1: Check if config file can be loaded
echo "<h2>1. Configuration File Test</h2>";
try {
    if (file_exists('../config.php')) {
        echo "<p style='color: green;'>✅ config.php file found</p>";
        
        // Don't include config yet, just check syntax
        $config_content = file_get_contents('../config.php');
        if ($config_content !== false) {
            echo "<p style='color: green;'>✅ config.php file readable</p>";
        } else {
            echo "<p style='color: red;'>❌ Cannot read config.php file</p>";
            exit;
        }
    } else {
        echo "<p style='color: red;'>❌ config.php file not found</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking config file: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Parse config values
echo "<h2>2. Configuration Values</h2>";
preg_match("/define\('DB_HOST', '([^']+)'\)/", $config_content, $host_matches);
preg_match("/define\('DB_USER', '([^']+)'\)/", $config_content, $user_matches);
preg_match("/define\('DB_PASS', '([^']+)'\)/", $config_content, $pass_matches);
preg_match("/define\('DB_NAME', '([^']+)'\)/", $config_content, $name_matches);

$db_host = $host_matches[1] ?? 'NOT FOUND';
$db_user = $user_matches[1] ?? 'NOT FOUND';
$db_pass = $pass_matches[1] ?? 'NOT FOUND';
$db_name = $name_matches[1] ?? 'NOT FOUND';

echo "<p>DB_HOST: " . htmlspecialchars($db_host) . "</p>";
echo "<p>DB_USER: " . htmlspecialchars($db_user) . "</p>";
echo "<p>DB_NAME: " . htmlspecialchars($db_name) . "</p>";
echo "<p>DB_PASS: " . (empty($db_pass) ? 'EMPTY' : 'SET (hidden)') . "</p>";

// Check for placeholder values
$has_placeholders = false;
if ($db_user === 'your_username' || strpos($db_user, 'your_') !== false) {
    echo "<p style='color: orange;'>⚠️ DB_USER contains placeholder value - update config.php</p>";
    $has_placeholders = true;
}
if ($db_pass === 'your_password' || strpos($db_pass, 'your_') !== false) {
    echo "<p style='color: orange;'>⚠️ DB_PASS contains placeholder value - update config.php</p>";
    $has_placeholders = true;
}
if ($db_name === 'furom_db' || strpos($db_name, 'your_') !== false) {
    echo "<p style='color: orange;'>⚠️ DB_NAME contains placeholder value - update config.php</p>";
    $has_placeholders = true;
}

if ($has_placeholders) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
    echo "<h3>🔧 Fix Required:</h3>";
    echo "<p>You need to update your <code>config.php</code> file with actual cPanel database credentials:</p>";
    echo "<ol>";
    echo "<li>Log into your cPanel</li>";
    echo "<li>Go to 'MySQL Databases'</li>";
    echo "<li>Note your database name, username, and password</li>";
    echo "<li>Update the DB_USER, DB_PASS, and DB_NAME values in config.php</li>";
    echo "<li>Upload the updated config.php to your server</li>";
    echo "</ol>";
    echo "</div>";
    exit;
}

// Test 3: Database Connection
echo "<h2>3. Database Connection Test</h2>";
try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Test 4: Check required tables
    echo "<h2>4. Database Schema Test</h2>";
    $required_tables = ['users', 'posts', 'comments', 'categories', 'votes', 'reports'];
    $existing_tables = [];
    
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch()) {
        $existing_tables[] = reset($row);
    }
    
    foreach ($required_tables as $table) {
        if (in_array($table, $existing_tables)) {
            echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' missing</p>";
        }
    }
    
    // Test 5: Sample data
    echo "<h2>5. Data Test</h2>";
    try {
        $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "<p>Users in database: $user_count</p>";
        
        $post_count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        echo "<p>Posts in database: $post_count</p>";
        
        echo "<p style='color: green;'>✅ Database queries working</p>";
        
        // Test 6: Admin user check
        echo "<h2>6. Admin User Test</h2>";
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
        $stmt->execute();
        $admin_user = $stmt->fetch();
        
        if ($admin_user) {
            echo "<p style='color: green;'>✅ Admin user found</p>";
            echo "<p>Admin user ID: " . $admin_user['id'] . "</p>";
            echo "<p>Admin EXP: " . $admin_user['exp'] . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No admin user found - you may need to create one</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Data test failed: " . $e->getMessage() . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
    echo "<h3>🔧 Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Double-check your database credentials in config.php</li>";
    echo "<li>Verify the database exists in cPanel</li>";
    echo "<li>Confirm the database user has proper permissions</li>";
    echo "<li>Check if the database prefix matches your cPanel username</li>";
    echo "<li>Review server error logs for more details</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<h2>✅ Test Complete</h2>";
echo "<p><a href='minimal-dashboard.php'>Try Minimal Dashboard</a></p>";
echo "<p><a href='dashboard.php'>Try Full Dashboard</a></p>";
echo "<p><a href='../index.php'>Return to Main Site</a></p>";
?>