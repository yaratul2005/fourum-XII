<?php
// Direct script to create missing tables
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Creating Missing Database Tables</h1>";

try {
    require_once '../config.php';
    echo "<p style='color: green;'>✅ Configuration loaded</p>";
    
    // Create missing tables
    $missing_tables = [
        'votes' => "CREATE TABLE IF NOT EXISTS `votes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `target_type` enum('post','comment') NOT NULL,
            `target_id` int(11) NOT NULL,
            `vote_type` enum('up','down') NOT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_vote` (`user_id`, `target_type`, `target_id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        'reports' => "CREATE TABLE IF NOT EXISTS `reports` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `target_type` enum('post','comment','user') NOT NULL,
            `target_id` int(11) NOT NULL,
            `reason` varchar(255) NOT NULL,
            `status` enum('pending','resolved','dismissed') DEFAULT 'pending',
            `resolved_by` int(11) DEFAULT NULL,
            `resolved_at` datetime DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        'exp_log' => "CREATE TABLE IF NOT EXISTS `exp_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `amount` int(11) NOT NULL,
            `reason` varchar(255) NOT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    ];
    
    echo "<h2>Creating Tables...</h2>";
    
    foreach ($missing_tables as $table_name => $create_sql) {
        try {
            // Check if table exists
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table_name]);
            
            if ($stmt->rowCount() == 0) {
                // Table doesn't exist, create it
                $pdo->exec($create_sql);
                echo "<p style='color: green;'>✅ Created table '$table_name'</p>";
            } else {
                echo "<p style='color: blue;'>ℹ️ Table '$table_name' already exists</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error creating table '$table_name': " . $e->getMessage() . "</p>";
        }
    }
    
    // Verify tables exist now
    echo "<h2>Verification:</h2>";
    $check_tables = ['votes', 'reports', 'exp_log'];
    foreach ($check_tables as $table) {
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: green;'>✅ Table '$table' confirmed</p>";
            } else {
                echo "<p style='color: red;'>❌ Table '$table' still missing</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Verification error for '$table': " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>✅ Table Creation Complete</h2>";
    echo "<p><a href='robust-dashboard.php'>Try Robust Dashboard</a></p>";
    echo "<p><a href='dashboard.php'>Try Original Dashboard</a></p>";
    echo "<p><a href='users.php'>Try User Management</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Fatal error: " . $e->getMessage() . "</p>";
}
?>