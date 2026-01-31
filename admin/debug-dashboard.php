<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Admin Dashboard Debug</h1>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";

// Test basic PHP functionality
echo "<h2>Basic Tests:</h2>";
echo "<p>✅ PHP is working</p>";

// Test file includes
echo "<h2>File Include Tests:</h2>";
try {
    if (file_exists('../config.php')) {
        echo "<p>✅ config.php exists</p>";
        require_once '../config.php';
        echo "<p>✅ config.php loaded successfully</p>";
    } else {
        echo "<p>❌ config.php not found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error loading config.php: " . $e->getMessage() . "</p>";
}

try {
    if (file_exists('../includes/functions.php')) {
        echo "<p>✅ functions.php exists</p>";
        require_once '../includes/functions.php';
        echo "<p>✅ functions.php loaded successfully</p>";
    } else {
        echo "<p>❌ functions.php not found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error loading functions.php: " . $e->getMessage() . "</p>";
}

// Test database connection
echo "<h2>Database Tests:</h2>";
try {
    if (isset($pdo)) {
        echo "<p>✅ Database connection available</p>";
        $stmt = $pdo->query("SELECT 1");
        if ($stmt) {
            echo "<p>✅ Database query working</p>";
        }
    } else {
        echo "<p>❌ Database connection not established</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test session
echo "<h2>Session Tests:</h2>";
try {
    if (!isset($_SESSION)) {
        session_start();
    }
    echo "<p>✅ Session started successfully</p>";
    $_SESSION['debug_test'] = 'working';
    echo "<p>✅ Session variables working</p>";
} catch (Exception $e) {
    echo "<p>❌ Session error: " . $e->getMessage() . "</p>";
}

// Test admin access
echo "<h2>Admin Access Tests:</h2>";
try {
    if (function_exists('is_logged_in')) {
        echo "<p>✅ is_logged_in function available</p>";
        $logged_in = is_logged_in();
        echo "<p>Login status: " . ($logged_in ? 'Logged in' : 'Not logged in') . "</p>";
        
        if ($logged_in) {
            $user_id = get_current_user_id();
            echo "<p>Current user ID: " . $user_id . "</p>";
            
            if (function_exists('get_user_data')) {
                $user_data = get_user_data($user_id);
                if ($user_data) {
                    echo "<p>Username: " . htmlspecialchars($user_data['username']) . "</p>";
                    echo "<p>Is admin: " . ($user_data['username'] === 'admin' ? 'Yes' : 'No') . "</p>";
                }
            }
        }
    } else {
        echo "<p>❌ is_logged_in function not available</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Admin access error: " . $e->getMessage() . "</p>";
}

echo "<h2>File Path Tests:</h2>";
echo "<p>Current script: " . __FILE__ . "</p>";
echo "<p>Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "</p>";
echo "<p>Script filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'Not set') . "</p>";

echo "<h2>Directory Permissions:</h2>";
$dirs_to_check = ['../', '../assets/', '../assets/css/', './'];
foreach ($dirs_to_check as $dir) {
    if (is_dir($dir)) {
        echo "<p>" . htmlspecialchars($dir) . ": " . (is_readable($dir) ? 'Readable' : 'NOT Readable') . "</p>";
    } else {
        echo "<p>" . htmlspecialchars($dir) . ": Directory does not exist</p>";
    }
}

echo "<h2>File Existence:</h2>";
$files_to_check = [
    '../config.php',
    '../includes/functions.php', 
    '../assets/css/admin.css',
    '../assets/css/style.css'
];
foreach ($files_to_check as $file) {
    echo "<p>" . htmlspecialchars($file) . ": " . (file_exists($file) ? 'Exists' : 'Does NOT exist') . "</p>";
}

echo "<h2>Debug Complete</h2>";
echo "<p><a href='dashboard.php'>Try accessing normal dashboard</a></p>";
echo "<p><a href='../index.php'>Return to main site</a></p>";
?>