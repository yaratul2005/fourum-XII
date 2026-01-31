<?php
// Smart 404 Handler with Intelligent Redirects
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the requested URL
$requested_url = $_SERVER['REQUEST_URI'];
$clean_url = str_replace(['.php', '.html'], '', $requested_url);
$search_term = urldecode(basename($clean_url));

// Log 404 for analytics
error_log("404 Error: " . $requested_url . " - Referrer: " . ($_SERVER['HTTP_REFERER'] ?? 'Direct'));

// Smart redirect logic
$redirect_suggestions = [];

// Try to find similar content
try {
    require_once 'config.php';
    
    // Search for similar posts by title
    $stmt = $pdo->prepare("SELECT id, title FROM posts WHERE title LIKE ? OR content LIKE ? ORDER BY score DESC LIMIT 5");
    $search_pattern = "%$search_term%";
    $stmt->execute([$search_pattern, $search_pattern]);
    $similar_posts = $stmt->fetchAll();
    
    foreach ($similar_posts as $post) {
        $redirect_suggestions[] = [
            'url' => '/post.php?id=' . $post['id'],
            'title' => $post['title'],
            'type' => 'Post'
        ];
    }
    
    // Search for similar usernames
    $stmt = $pdo->prepare("SELECT username FROM users WHERE username LIKE ? ORDER BY exp DESC LIMIT 3");
    $stmt->execute([$search_pattern]);
    $similar_users = $stmt->fetchAll();
    
    foreach ($similar_users as $user) {
        $redirect_suggestions[] = [
            'url' => '/profile.php?username=' . urlencode($user['username']),
            'title' => $user['username'],
            'type' => 'User Profile'
        ];
    }
    
    // Check if it's a category request
    $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE name LIKE ? LIMIT 3");
    $stmt->execute([$search_pattern]);
    $similar_categories = $stmt->fetchAll();
    
    foreach ($similar_categories as $category) {
        $redirect_suggestions[] = [
            'url' => '/index.php?category=' . $category['id'],
            'title' => $category['name'],
            'type' => 'Category'
        ];
    }
    
} catch (Exception $e) {
    // Silently fail if database connection fails
    error_log("404 handler database error: " . $e->getMessage());
}

// Common redirect patterns
$common_redirects = [
    '/login' => '/login.php',
    '/signin' => '/login.php',
    '/register' => '/register.php',
    '/signup' => '/register.php',
    '/home' => '/index.php',
    '/forum' => '/index.php',
    '/discuss' => '/index.php',
    '/admin' => '/admin/dashboard.php',
    '/dashboard' => '/admin/dashboard.php'
];

// Check for exact matches in common redirects
foreach ($common_redirects as $pattern => $redirect) {
    if (stripos($requested_url, $pattern) !== false) {
        header("Location: $redirect", true, 301);
        exit();
    }
}

// Set proper 404 headers
http_response_code(404);
header('Cache-Control: no-cache, must-revalidate');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - <?php echo defined('SITE_NAME') ? SITE_NAME : 'Furom'; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .error-container {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .error-content {
            text-align: center;
            max-width: 600px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .error-code {
            font-size: 6rem;
            font-weight: 800;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            text-shadow: 0 0 20px rgba(255, 107, 107, 0.3);
        }
        .error-title {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #fff;
        }
        .error-message {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            color: #ccc;
            line-height: 1.6;
        }
        .suggestions {
            margin: 2rem 0;
            text-align: left;
        }
        .suggestion-item {
            padding: 0.8rem;
            margin: 0.5rem 0;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        .suggestion-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        .suggestion-link {
            color: #4ecdc4;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .suggestion-link:hover {
            color: #ff6b6b;
        }
        .suggestion-type {
            background: rgba(78, 205, 196, 0.2);
            padding: 2px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            color: #4ecdc4;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }
        .btn {
            padding: 12px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .btn-primary {
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            color: white;
        }
        .btn-secondary {
            background: transparent;
            color: #4ecdc4;
            border-color: #4ecdc4;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        .search-box {
            margin: 2rem 0;
        }
        .search-input {
            width: 100%;
            padding: 15px;
            border-radius: 50px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            background: rgba(0, 0, 0, 0.2);
            color: white;
            font-size: 1.1rem;
            outline: none;
            transition: all 0.3s ease;
        }
        .search-input:focus {
            border-color: #4ecdc4;
            box-shadow: 0 0 20px rgba(78, 205, 196, 0.3);
        }
        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <!-- Particles Background -->
    <div id="particles-js"></div>
    
    <div class="error-container">
        <div class="error-content">
            <div class="error-code">404</div>
            <h1 class="error-title">Lost in Cyberspace</h1>
            <p class="error-message">
                The page you're looking for seems to have vanished into the digital void. 
                Don't worry, even the best explorers sometimes lose their way in the vast expanse of the internet.
            </p>
            
            <?php if (!empty($redirect_suggestions)): ?>
                <div class="suggestions">
                    <h3>Did you mean one of these?</h3>
                    <?php foreach ($redirect_suggestions as $suggestion): ?>
                        <div class="suggestion-item">
                            <a href="<?php echo htmlspecialchars($suggestion['url']); ?>" class="suggestion-link">
                                <i class="fas fa-arrow-right"></i>
                                <span><?php echo htmlspecialchars($suggestion['title']); ?></span>
                                <span class="suggestion-type"><?php echo htmlspecialchars($suggestion['type']); ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="search-box">
                <input type="text" class="search-input" id="searchInput" placeholder="Search our forum..." autocomplete="off">
            </div>
            
            <div class="action-buttons">
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-home"></i> Return Home
                </a>
                <a href="/index.php" class="btn btn-secondary">
                    <i class="fas fa-comments"></i> Browse Forum
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        // Initialize particles
        particlesJS('particles-js', {
            particles: {
                number: { value: 80, density: { enable: true, value_area: 800 } },
                color: { value: '#4ecdc4' },
                shape: { type: 'circle' },
                opacity: { value: 0.5, random: true },
                size: { value: 3, random: true },
                line_linked: { enable: true, distance: 150, color: '#4ecdc4', opacity: 0.4, width: 1 },
                move: { enable: true, speed: 2, direction: 'none', random: true, straight: false, out_mode: 'out' }
            },
            interactivity: {
                detect_on: 'canvas',
                events: { onhover: { enable: true, mode: 'repulse' }, onclick: { enable: true, mode: 'push' } }
            }
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm) {
                    window.location.href = '/index.php?search=' + encodeURIComponent(searchTerm);
                }
            }
        });

        // Auto-focus search after delay
        setTimeout(() => {
            document.getElementById('searchInput').focus();
        }, 1000);
    </script>
</body>
</html>