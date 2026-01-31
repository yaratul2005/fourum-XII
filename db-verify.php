<?php
// Database Verification Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

try {
    echo "<h2>Database Verification</h2>\n";
    
    // Check database connection
    echo "<p>✅ Database connection: SUCCESS</p>\n";
    
    // Check if settings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Settings table exists</p>\n";
        
        // Check Google configuration
        $stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'google_%'");
        $google_settings = [];
        while ($row = $stmt->fetch()) {
            $google_settings[str_replace('google_', '', $row['setting_key'])] = $row['setting_value'];
        }
        
        echo "<h3>Google Configuration:</h3>\n";
        echo "<ul>\n";
        foreach ($google_settings as $key => $value) {
            if ($key === 'client_secret') {
                $value = str_repeat('*', strlen($value));
            }
            echo "<li><strong>$key:</strong> $value</li>\n";
        }
        echo "</ul>\n";
        
        // Check if Google is enabled
        if (isset($google_settings['enabled']) && $google_settings['enabled'] == '1') {
            echo "<p>✅ Google authentication is ENABLED</p>\n";
        } else {
            echo "<p>⚠️ Google authentication is DISABLED</p>\n";
        }
        
    } else {
        echo "<p>❌ Settings table does not exist</p>\n";
        echo "<p>You need to create the settings table first. Run the initialization script:</p>\n";
        echo "<p><a href='admin/init-settings-table.php'>Initialize Settings Table</a></p>\n";
    }
    
    // Check users table
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Users table exists</p>\n";
    } else {
        echo "<p>❌ Users table does not exist</p>\n";
    }
    
    echo "<hr>\n";
    echo "<p><a href='/'>← Back to Homepage</a></p>\n";
    echo "<p><a href='/admin/dashboard.php'>→ Admin Dashboard</a></p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>\n";
    echo "<p>Please check your database configuration in config.php</p>\n";
}
?>