<?php
// Database Connection Tester
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Database Connection Test</h2>";

// Test different connection methods
$hosts_to_test = ['localhost', '127.0.0.1', '::1'];
$ports_to_test = [3306, 3307]; // Common MySQL ports

echo "<h3>Testing Connection Parameters:</h3>";

// Get connection info from user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? '';
    
    echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h4>Trying Connection:</h4>";
    echo "Host: <strong>" . htmlspecialchars($db_host) . "</strong><br>";
    echo "User: <strong>" . htmlspecialchars($db_user) . "</strong><br>";
    echo "Database: <strong>" . htmlspecialchars($db_name) . "</strong><br>";
    echo "</div>";
    
    // Test basic connection (without database)
    echo "<h3>1. Testing Server Connection (without database):</h3>";
    try {
        $pdo_test = new PDO("mysql:host=$db_host;charset=utf8", $db_user, $db_pass);
        $pdo_test->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<div style='color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "✅ Successfully connected to MySQL server!";
        echo "</div>";
        
        // List available databases
        echo "<h4>Available Databases:</h4>";
        $stmt = $pdo_test->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<ul>";
        foreach($databases as $db) {
            $selected = ($db === $db_name) ? " (TARGET)" : "";
            echo "<li>" . htmlspecialchars($db) . $selected . "</li>";
        }
        echo "</ul>";
        
        // Test specific database connection
        echo "<h3>2. Testing Specific Database Connection:</h3>";
        try {
            $pdo_db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
            $pdo_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<div style='color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
            echo "✅ Successfully connected to database '$db_name'!";
            echo "</div>";
            
            // Test creating a sample table
            echo "<h3>3. Testing Table Creation:</h3>";
            try {
                $pdo_db->exec("CREATE TABLE IF NOT EXISTS test_connection (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    test_data VARCHAR(100),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                
                echo "<div style='color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
                echo "✅ Successfully created test table!";
                echo "</div>";
                
                // Insert test data
                $stmt = $pdo_db->prepare("INSERT INTO test_connection (test_data) VALUES (?)");
                $stmt->execute(['Connection test successful']);
                
                echo "<div style='color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
                echo "✅ Successfully inserted test data!";
                echo "</div>";
                
                // Read test data
                $stmt = $pdo_db->query("SELECT * FROM test_connection ORDER BY id DESC LIMIT 1");
                $test_row = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<p><strong>Test data retrieved:</strong> " . htmlspecialchars($test_row['test_data']) . "</p>";
                
                // Clean up
                $pdo_db->exec("DROP TABLE IF EXISTS test_connection");
                echo "<div style='color: blue; padding: 10px; background: #cce7ff; border: 1px solid #99d6ff; border-radius: 5px;'>";
                echo "ℹ️ Test table cleaned up successfully";
                echo "</div>";
                
            } catch(PDOException $e) {
                echo "<div style='color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>";
                echo "❌ Table creation failed: " . $e->getMessage();
                echo "</div>";
            }
            
        } catch(PDOException $e) {
            echo "<div style='color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>";
            echo "❌ Database connection failed: " . $e->getMessage();
            echo "</div>";
            
            // Suggest solutions
            echo "<h4>Possible Solutions:</h4>";
            echo "<ul>";
            echo "<li>Check if database '$db_name' exists</li>";
            echo "<li>Verify database user has proper permissions</li>";
            echo "<li>Check if MySQL service is running</li>";
            echo "<li>Try different host (localhost, 127.0.0.1, ::1)</li>";
            echo "</ul>";
        }
        
    } catch(PDOException $e) {
        echo "<div style='color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "❌ Server connection failed: " . $e->getMessage();
        echo "</div>";
        
        // Detailed error analysis
        echo "<h4>Detailed Error Analysis:</h4>";
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; border: 1px solid #ffeaa7;'>";
        
        if (strpos($e->getMessage(), 'Connection refused') !== false) {
            echo "<strong>Connection Refused:</strong><br>";
            echo "• MySQL server may not be running<br>";
            echo "• Wrong host/port specified<br>";
            echo "• Firewall blocking connection<br>";
            echo "• Socket file permissions issue<br>";
        } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
            echo "<strong>Access Denied:</strong><br>";
            echo "• Incorrect username/password<br>";
            echo "• User doesn't have permission to connect<br>";
            echo "• User account doesn't exist<br>";
        } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
            echo "<strong>Database Not Found:</strong><br>";
            echo "• Database doesn't exist<br>";
            echo "• Typo in database name<br>";
            echo "• User doesn't have access to database<br>";
        }
        
        echo "</div>";
    }
    
} else {
    // Show form
    echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>Enter Your Database Credentials:</h3>";
    echo "<form method='POST'>";
    echo "<div style='margin: 10px 0;'>";
    echo "<label>Database Host:</label><br>";
    echo "<input type='text' name='db_host' value='localhost' style='width: 300px; padding: 8px;'>";
    echo "</div>";
    echo "<div style='margin: 10px 0;'>";
    echo "<label>Database Username:</label><br>";
    echo "<input type='text' name='db_user' style='width: 300px; padding: 8px;'>";
    echo "</div>";
    echo "<div style='margin: 10px 0;'>";
    echo "<label>Database Password:</label><br>";
    echo "<input type='password' name='db_pass' style='width: 300px; padding: 8px;'>";
    echo "</div>";
    echo "<div style='margin: 10px 0;'>";
    echo "<label>Database Name:</label><br>";
    echo "<input type='text' name='db_name' value='furom_db' style='width: 300px; padding: 8px;'>";
    echo "</div>";
    echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test Connection</button>";
    echo "</form>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Common Hosting Control Panel Database Info:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px;'>";
echo "<p><strong>cPanel:</strong> Database name is usually 'username_databasename'</p>";
echo "<p><strong>Plesk:</strong> Check database section for connection details</p>";
echo "<p><strong>DirectAdmin:</strong> Database prefix is your account username</p>";
echo "<p><strong>Cloud/VPS:</strong> May need to create database first via phpMyAdmin</p>";
echo "</div>";

echo "<h3>Troubleshooting Tips:</h3>";
echo "<ol>";
echo "<li><strong>Check hosting control panel</strong> - Look for database section</li>";
echo "<li><strong>Verify credentials</strong> - Copy exactly from control panel</li>";
echo "<li><strong>Test with phpMyAdmin</strong> - If available, test connection there first</li>";
echo "<li><strong>Contact support</strong> - Your hosting provider can help with connection details</li>";
echo "<li><strong>Check error logs</strong> - Look for more detailed error information</li>";
echo "</ol>";
?>