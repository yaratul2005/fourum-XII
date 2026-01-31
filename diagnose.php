<?php
// Diagnostic Script for Furom
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Furom Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .test { margin: 15px 0; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        code { background: #e9ecef; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔍 Furom Diagnostic Report</h1>";

// Test 1: Configuration file
echo "<div class='test info'>";
echo "<h3>1. Configuration File Check</h3>";
if (file_exists('config.php')) {
    echo "<div class='success'>✅ config.php exists</div>";
    
    // Try to include and test config
    try {
        require_once 'config.php';
        echo "<div class='success'>✅ config.php loaded successfully</div>";
        
        // Test database connection
        echo "<div class='test info'>";
        echo "<h4>Database Connection Test:</h4>";
        try {
            $stmt = $pdo->query("SELECT 1");
            echo "<div class='success'>✅ Database connection working</div>";
        } catch(Exception $e) {
            echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
        }
        echo "</div>";
        
    } catch(Exception $e) {
        echo "<div class='error'>❌ Error loading config.php: " . $e->getMessage() . "</div>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
} else {
    echo "<div class='error'>❌ config.php not found</div>";
}
echo "</div>";

// Test 2: Required files
echo "<div class='test info'>";
echo "<h3>2. Required Files Check</h3>";

$required_files = [
    'includes/functions.php' => 'Functions library',
    'assets/css/style.css' => 'Main stylesheet',
    'assets/js/main.js' => 'Main JavaScript'
];

foreach($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "<div class='success'>✅ $description ($file)</div>";
    } else {
        echo "<div class='error'>❌ $description ($file) - MISSING</div>";
    }
}
echo "</div>";

// Test 3: Database tables
echo "<div class='test info'>";
echo "<h3>3. Database Tables Check</h3>";

if (isset($pdo)) {
    $required_tables = ['users', 'posts', 'comments', 'categories', 'post_votes', 'comment_votes'];
    
    foreach($required_tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<div class='success'>✅ Table '$table' exists</div>";
            } else {
                echo "<div class='error'>❌ Table '$table' missing</div>";
            }
        } catch(Exception $e) {
            echo "<div class='error'>❌ Error checking table '$table': " . $e->getMessage() . "</div>";
        }
    }
    
    // Check if there's data
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
        $category_count = $stmt->fetchColumn();
        echo "<div class='info'>📊 Found $category_count categories in database</div>";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $user_count = $stmt->fetchColumn();
        echo "<div class='info'>👥 Found $user_count users in database</div>";
    } catch(Exception $e) {
        echo "<div class='warning'>⚠️ Could not count records: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='error'>❌ Cannot check tables - database not connected</div>";
}
echo "</div>";

// Test 4: PHP Configuration
echo "<div class='test info'>";
echo "<h3>4. PHP Configuration Check</h3>";

$required_extensions = ['pdo', 'pdo_mysql', 'session', 'json', 'openssl'];
foreach($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='success'>✅ Extension '$ext' loaded</div>";
    } else {
        echo "<div class='error'>❌ Extension '$ext' NOT loaded</div>";
    }
}

echo "<div class='info'>PHP Version: " . PHP_VERSION . "</div>";
echo "<div class='info'>Memory Limit: " . ini_get('memory_limit') . "</div>";
echo "<div class='info'>Max Execution Time: " . ini_get('max_execution_time') . " seconds</div>";
echo "</div>";

// Test 5: Try to simulate index.php loading
echo "<div class='test info'>";
echo "<h3>5. Index.php Simulation Test</h3>";

if (file_exists('config.php') && file_exists('includes/functions.php')) {
    try {
        // Simulate what index.php does
        require_once 'config.php';
        
        // Test getting posts
        $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT p.*, u.username, u.exp, 
                  (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id AND c.status = 'active') as comment_count
                  FROM posts p 
                  JOIN users u ON p.user_id = u.id 
                  WHERE p.status = 'active' 
                  ORDER BY p.score DESC, p.created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$limit, $offset]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='success'>✅ Query executed successfully</div>";
        echo "<div class='info'>Found " . count($posts) . " posts</div>";
        
        // Test getting categories
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<div class='info'>Found " . count($categories) . " categories</div>";
        
        // Test getting top users
        $stmt = $pdo->query("SELECT id, username, exp FROM users ORDER BY exp DESC LIMIT 5");
        $top_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<div class='info'>Found " . count($top_users) . " top users</div>";
        
    } catch(Exception $e) {
        echo "<div class='error'>❌ Error in simulation: " . $e->getMessage() . "</div>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
} else {
    echo "<div class='error'>❌ Cannot run simulation - missing required files</div>";
}
echo "</div>";

// Test 6: File permissions
echo "<div class='test info'>";
echo "<h3>6. File Permissions Check</h3>";

$directories = ['assets', 'assets/css', 'assets/js', 'includes', 'database'];
foreach($directories as $dir) {
    if (is_dir($dir)) {
        if (is_readable($dir)) {
            echo "<div class='success'>✅ Directory '$dir' is readable</div>";
        } else {
            echo "<div class='error'>❌ Directory '$dir' is NOT readable</div>";
        }
        
        if (is_writable($dir)) {
            echo "<div class='success'>✅ Directory '$dir' is writable</div>";
        } else {
            echo "<div class='warning'>⚠️ Directory '$dir' is NOT writable</div>";
        }
    } else {
        echo "<div class='error'>❌ Directory '$dir' does not exist</div>";
    }
}
echo "</div>";

// Summary
echo "<div class='test success'>";
echo "<h3>📋 Diagnostic Summary</h3>";
echo "<p>If you see mostly green checks above, your installation should work. If there are red errors, those need to be fixed.</p>";
echo "<p><strong>Common fixes:</strong></p>";
echo "<ul>";
echo "<li>Missing files: Re-upload the missing files</li>";
echo "<li>Database issues: Check your database connection in config.php</li>";
echo "<li>Permission issues: Check file permissions via FTP/cPanel</li>";
echo "<li>PHP extensions: Contact your hosting provider</li>";
echo "</ul>";
echo "</div>";

// Quick links
echo "<div class='test info'>";
echo "<h3>🔗 Quick Links</h3>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";
echo "<a href='index.php' style='background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Try Index.php</a>";
echo "<a href='login.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Login Page</a>";
echo "<a href='create-post.php' style='background: #ffc107; color: black; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Create Post</a>";
echo "</div>";
echo "</div>";

echo "</div></body></html>";
?>