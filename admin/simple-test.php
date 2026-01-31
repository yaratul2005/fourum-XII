<?php
// Simple test to check what's working
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Admin Test</h1>";

// Test 1: Load config
echo "<h2>Loading Config...</h2>";
try {
    require_once '../config.php';
    echo "<p style='color: green;'>✅ Config loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Config error: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Load functions
echo "<h2>Loading Functions...</h2>";
try {
    require_once '../includes/functions.php';
    echo "<p style='color: green;'>✅ Functions loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Functions error: " . $e->getMessage() . "</p>";
    exit;
}

// Test 3: Database connection
echo "<h2>Database Test...</h2>";
try {
    if (isset($pdo)) {
        echo "<p style='color: green;'>✅ Database connected</p>";
        
        // Test simple queries
        $queries = [
            "users" => "SELECT COUNT(*) as count FROM users",
            "posts" => "SELECT COUNT(*) as count FROM posts",
            "comments" => "SELECT COUNT(*) as count FROM comments"
        ];
        
        foreach ($queries as $table => $query) {
            try {
                $stmt = $pdo->query($query);
                $result = $stmt->fetch();
                echo "<p style='color: green;'>✅ $table table: " . $result['count'] . " records</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ $table table error: " . $e->getMessage() . "</p>";
            }
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 4: Session
echo "<h2>Session Test...</h2>";
try {
    session_start();
    echo "<p style='color: green;'>✅ Session started</p>";
    
    if (is_logged_in()) {
        echo "<p style='color: green;'>✅ User logged in</p>";
        $user_data = get_user_data(get_current_user_id());
        if ($user_data && $user_data['username'] === 'admin') {
            echo "<p style='color: green;'>✅ Admin access confirmed</p>";
        } else {
            echo "<p style='color: red;'>❌ Not admin user</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Not logged in - redirecting to login</p>";
        // Don't redirect in test mode
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Session error: " . $e->getMessage() . "</p>";
}

echo "<h2>Test Complete</h2>";
echo "<p><a href='dashboard.php'>Try Dashboard</a></p>";
echo "<p><a href='users.php'>Try Users Page</a></p>";
echo "<p><a href='backup.php'>Backup Page (working)</a></p>";
?>