<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Admin Panel Diagnostic Tool</h1>";
echo "<p>Testing each admin component individually...</p>";

// Test 1: Basic PHP functionality
echo "<h2>1. Basic PHP Tests</h2>";
echo "<p>✅ PHP Version: " . PHP_VERSION . "</p>";
echo "<p>✅ Current Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>✅ Script Location: " . __FILE__ . "</p>";

// Test 2: File existence
echo "<h2>2. Required File Tests</h2>";
$required_files = [
    '../config.php' => 'Configuration file',
    '../includes/functions.php' => 'Functions library',
    '../assets/css/admin.css' => 'Admin stylesheet'
];

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $description exists</p>";
    } else {
        echo "<p style='color: red;'>❌ $description missing ($file)</p>";
    }
}

// Test 3: Configuration loading
echo "<h2>3. Configuration Loading Test</h2>";
try {
    require_once '../config.php';
    echo "<p style='color: green;'>✅ config.php loaded successfully</p>";
    echo "<p>DB_HOST: " . DB_HOST . "</p>";
    echo "<p>DB_NAME: " . DB_NAME . "</p>";
    echo "<p>SITE_NAME: " . SITE_NAME . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Failed to load config.php: " . $e->getMessage() . "</p>";
    exit;
}

// Test 4: Functions loading
echo "<h2>4. Functions Loading Test</h2>";
try {
    require_once '../includes/functions.php';
    echo "<p style='color: green;'>✅ functions.php loaded successfully</p>";
    
    // Test key functions
    $functions_to_test = ['is_logged_in', 'get_current_user_id', 'get_user_data'];
    foreach ($functions_to_test as $func) {
        if (function_exists($func)) {
            echo "<p style='color: green;'>✅ Function $func() available</p>";
        } else {
            echo "<p style='color: red;'>❌ Function $func() missing</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Failed to load functions.php: " . $e->getMessage() . "</p>";
    exit;
}

// Test 5: Database connection
echo "<h2>5. Database Connection Test</h2>";
try {
    if (isset($pdo)) {
        echo "<p style='color: green;'>✅ Database connection established</p>";
        
        // Test basic query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        if ($result['test'] == 1) {
            echo "<p style='color: green;'>✅ Database query working</p>";
        }
        
        // Check required tables
        echo "<h3>Database Tables Check:</h3>";
        $required_tables = ['users', 'posts', 'comments', 'categories', 'votes', 'reports'];
        foreach ($required_tables as $table) {
            try {
                $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                if ($stmt->rowCount() > 0) {
                    echo "<p style='color: green;'>✅ Table '$table' exists</p>";
                } else {
                    echo "<p style='color: red;'>❌ Table '$table' missing</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error checking table '$table': " . $e->getMessage() . "</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Database connection not established</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test 6: Session and authentication
echo "<h2>6. Authentication Test</h2>";
try {
    session_start();
    echo "<p style='color: green;'>✅ Session started</p>";
    
    $logged_in = is_logged_in();
    echo "<p>Login status: " . ($logged_in ? 'Logged in' : 'Not logged in') . "</p>";
    
    if ($logged_in) {
        $user_id = get_current_user_id();
        echo "<p>User ID: $user_id</p>";
        
        $user_data = get_user_data($user_id);
        if ($user_data) {
            echo "<p>Username: " . htmlspecialchars($user_data['username']) . "</p>";
            echo "<p>Is admin: " . ($user_data['username'] === 'admin' ? 'Yes' : 'No') . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Authentication test failed: " . $e->getMessage() . "</p>";
}

// Test 7: Specific admin page tests
echo "<h2>7. Individual Admin Page Tests</h2>";

$admin_pages = [
    'dashboard.php' => 'Dashboard',
    'users.php' => 'User Management', 
    'posts.php' => 'Content Management',
    'reports.php' => 'Reports System',
    'settings.php' => 'Settings Panel'
];

foreach ($admin_pages as $page => $description) {
    echo "<h3>$description ($page)</h3>";
    
    // Test if file exists
    if (!file_exists($page)) {
        echo "<p style='color: red;'>❌ File not found</p>";
        continue;
    }
    
    // Test file syntax (first few lines)
    $content = file_get_contents($page);
    if ($content === false) {
        echo "<p style='color: red;'>❌ Cannot read file</p>";
        continue;
    }
    
    // Check for common issues
    if (strpos($content, 'require_once ../config.php') !== false) {
        echo "<p style='color: green;'>✅ Includes config.php</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ May not include config.php properly</p>";
    }
    
    if (strpos($content, 'require_once ../includes/functions.php') !== false) {
        echo "<p style='color: green;'>✅ Includes functions.php</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ May not include functions.php properly</p>";
    }
    
    // Count lines to estimate complexity
    $line_count = substr_count($content, "\n") + 1;
    echo "<p>Lines of code: $line_count</p>";
    
    echo "<p><a href='$page' target='_blank'>Try accessing page</a></p>";
}

echo "<h2>📋 Summary</h2>";
echo "<p><a href='minimal-dashboard.php'>Try Minimal Dashboard</a></p>";
echo "<p><a href='backup.php'>Backup Page (working)</a></p>";
echo "<p><a href='../index.php'>Return to Main Site</a></p>";

echo "<div style='margin-top: 30px; padding: 20px; background: #f0f0f0; border-radius: 5px;'>";
echo "<h3>🔧 Troubleshooting Steps:</h3>";
echo "<ol>";
echo "<li>If database tables are missing, you may need to run the installation script</li>";
echo "<li>If authentication fails, try logging in again</li>";
echo "<li>If specific pages fail, check server error logs for details</li>";
echo "<li>Ensure all required PHP extensions are installed</li>";
echo "</ol>";
echo "</div>";
?>