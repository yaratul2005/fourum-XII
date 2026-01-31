<?php
// Database Initialization Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Database Initialization</h1>";

try {
    require_once '../config.php';
    require_once '../includes/functions.php';
    
    echo "<p style='color: green;'>✅ Configuration loaded</p>";
    
    // List of required tables
    $required_tables = [
        'users' => "CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL UNIQUE,
            `email` varchar(100) NOT NULL UNIQUE,
            `password` varchar(255) NOT NULL,
            `exp` int(11) DEFAULT 0,
            `exp_reason` varchar(255) DEFAULT NULL,
            `level` varchar(50) DEFAULT 'Newbie',
            `avatar` varchar(255) DEFAULT NULL,
            `bio` text DEFAULT NULL,
            `verified` tinyint(1) DEFAULT 0,
            `verification_token` varchar(255) DEFAULT NULL,
            `reset_token` varchar(255) DEFAULT NULL,
            `reset_expires` datetime DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `last_login` datetime DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        'posts' => "CREATE TABLE IF NOT EXISTS `posts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `title` varchar(255) NOT NULL,
            `content` text NOT NULL,
            `category_id` int(11) DEFAULT NULL,
            `score` int(11) DEFAULT 0,
            `views` int(11) DEFAULT 0,
            `status` enum('active','deleted','featured') DEFAULT 'active',
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        'comments' => "CREATE TABLE IF NOT EXISTS `comments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `post_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `parent_id` int(11) DEFAULT NULL,
            `content` text NOT NULL,
            `score` int(11) DEFAULT 0,
            `status` enum('active','deleted') DEFAULT 'active',
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        'categories' => "CREATE TABLE IF NOT EXISTS `categories` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL UNIQUE,
            `description` text DEFAULT NULL,
            `color` varchar(7) DEFAULT '#3b82f6',
            `icon` varchar(50) DEFAULT 'tag',
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    ];
    
    echo "<h2>Creating Missing Tables...</h2>";
    
    $created_tables = 0;
    $errors = [];
    
    foreach ($required_tables as $table_name => $create_sql) {
        try {
            // Check if table exists
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table_name]);
            
            if ($stmt->rowCount() == 0) {
                // Table doesn't exist, create it
                $pdo->exec($create_sql);
                echo "<p style='color: green;'>✅ Created table '$table_name'</p>";
                $created_tables++;
            } else {
                echo "<p style='color: blue;'>ℹ️ Table '$table_name' already exists</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error creating table '$table_name': " . $e->getMessage() . "</p>";
            $errors[] = "Table $table_name: " . $e->getMessage();
        }
    }
    
    echo "<h2>Results</h2>";
    echo "<p>Tables processed: " . count($required_tables) . "</p>";
    echo "<p>Tables created: $created_tables</p>";
    echo "<p>Errors: " . count($errors) . "</p>";
    
    if (count($errors) > 0) {
        echo "<h3>Errors:</h3>";
        foreach ($errors as $error) {
            echo "<p style='color: red;'>$error</p>";
        }
    }
    
    // Test database functionality
    echo "<h2>Testing Database...</h2>";
    try {
        // Test basic queries
        $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "<p style='color: green;'>✅ Users table: $user_count records</p>";
        
        $post_count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        echo "<p style='color: green;'>✅ Posts table: $post_count records</p>";
        
        $comment_count = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
        echo "<p style='color: green;'>✅ Comments table: $comment_count records</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Database test failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>✅ Database Initialization Complete</h2>";
    echo "<p><a href='dashboard.php'>Try Dashboard</a></p>";
    echo "<p><a href='users.php'>Try Users Page</a></p>";
    echo "<p><a href='simple-test.php'>Run Simple Test</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Fatal error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>