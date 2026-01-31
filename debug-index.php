<?php
// Debug version of index.php with maximum error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h2>Debug Index.php - Detailed Error Report</h2>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Step 1: Config loading
echo "<h3>Step 1: Loading Configuration</h3>";
try {
    echo "Attempting to load config.php...<br>";
    require_once 'config.php';
    echo "✅ Config loaded successfully<br>";
    echo "SITE_URL: " . (defined('SITE_URL') ? SITE_URL : 'NOT DEFINED') . "<br>";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "<br>";
} catch (Exception $e) {
    echo "❌ Config loading failed: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . nl2br($e->getTraceAsString()) . "<br>";
    die();
} catch (Error $e) {
    echo "❌ Config loading fatal error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . nl2br($e->getTraceAsString()) . "<br>";
    die();
}

// Step 2: Database connection
echo "<h3>Step 2: Database Connection</h3>";
try {
    echo "Testing database connection...<br>";
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✅ Database connection working<br>";
    echo "Test query result: " . $result['test'] . "<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    die();
}

// Step 3: Query execution
echo "<h3>Step 3: Executing Queries</h3>";
try {
    // Test pagination variables
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    echo "Page: $page, Category: '$category', Limit: $limit, Offset: $offset<br>";
    
    // Build query
    $where_clause = "WHERE p.status = 'active'";
    $params = [];
    
    if ($category && $category !== 'all') {
        $where_clause .= " AND p.category = ?";
        $params[] = $category;
    }
    
    echo "Where clause: $where_clause<br>";
    echo "Parameters: " . json_encode($params) . "<br>";
    
    // Main query
    $query = "SELECT p.*, u.username, u.exp, 
              (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id AND c.status = 'active') as comment_count
              FROM posts p 
              JOIN users u ON p.user_id = u.id 
              $where_clause 
              ORDER BY p.score DESC, p.created_at DESC 
              LIMIT ? OFFSET ?";
    
    echo "Executing main query...<br>";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Main query executed successfully<br>";
    echo "Found " . count($posts) . " posts<br>";
    
    // Count query
    $count_query = "SELECT COUNT(*) FROM posts p $where_clause";
    echo "Executing count query: $count_query<br>";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_posts = $count_stmt->fetchColumn();
    $total_pages = ceil($total_posts / $limit);
    
    echo "✅ Count query executed successfully<br>";
    echo "Total posts: $total_posts, Total pages: $total_pages<br>";
    
    // Categories query
    echo "Fetching categories...<br>";
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Categories fetched: " . count($categories) . " categories<br>";
    
    // Top users query
    echo "Fetching top users...<br>";
    $stmt = $pdo->query("SELECT id, username, exp FROM users ORDER BY exp DESC LIMIT 5");
    $top_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Top users fetched: " . count($top_users) . " users<br>";
    
} catch (Exception $e) {
    echo "❌ Query execution failed: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . nl2br($e->getTraceAsString()) . "<br>";
    die();
}

// Step 4: Rendering HTML
echo "<h3>Step 4: Rendering HTML</h3>";
try {
    echo "Starting HTML output...<br>";
    
    // Basic HTML structure
    $html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Debug Furom</title>
        <link rel="stylesheet" href="assets/css/style.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body>
        <div style="padding: 20px; font-family: Arial, sans-serif;">
            <h1>🚀 Furom Debug Mode</h1>
            <p><strong>Status:</strong> All systems operational!</p>
            
            <div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;">
                <h3>📊 System Information</h3>
                <p>Posts found: ' . count($posts) . '</p>
                <p>Categories: ' . count($categories) . '</p>
                <p>Top users: ' . count($top_users) . '</p>
                <p>Current page: ' . $page . ' of ' . $total_pages . '</p>
            </div>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
                <h3>📝 Recent Posts</h3>';
    
    if (empty($posts)) {
        $html .= '<p>No posts found. Be the first to <a href="create-post.php">create a post</a>!</p>';
    } else {
        foreach($posts as $post) {
            $html .= '
                <div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;">
                    <h4>' . htmlspecialchars($post['title']) . '</h4>
                    <p>By: ' . htmlspecialchars($post['username']) . ' | Score: ' . $post['score'] . ' | Comments: ' . $post['comment_count'] . '</p>
                    <p>' . htmlspecialchars(substr($post['content'], 0, 150)) . '...</p>
                    <a href="post.php?id=' . $post['id'] . '">Read more</a>
                </div>';
        }
    }
    
    $html .= '
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 5px;">
                <h3>✅ Debug Results</h3>
                <p>All database queries executed successfully!</p>
                <p><a href="index.php">Try the normal index.php</a></p>
                <p><a href="login.php">Login page</a></p>
                <p><a href="create-post.php">Create post</a></p>
            </div>
        </div>
    </body>
    </html>';
    
    echo "✅ HTML generated successfully<br>";
    echo "Outputting HTML...<br>";
    
    // Output the HTML
    echo $html;
    
} catch (Exception $e) {
    echo "❌ HTML rendering failed: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . nl2br($e->getTraceAsString()) . "<br>";
}

echo "<h3>Debug Complete</h3>";
echo "<p>If you can see this page working, the issue is likely with the CSS/JS files or template rendering in the normal index.php.</p>";
?>