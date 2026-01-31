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
        /* Cyberpunk Theme Integration */
        :root {
            --cyber-primary: #00f5ff;
            --cyber-secondary: #ff00ff;
            --cyber-accent: #ff6b6b;
            --cyber-dark: #0a0a1a;
            --cyber-darker: #050510;
            --cyber-card: #121225;
            --cyber-text: #ffffff;
            --cyber-text-secondary: #a0a0c0;
            --cyber-border: #2a2a4a;
            --cyber-success: #00ff9d;
            --cyber-warning: #ffcc00;
            --cyber-danger: #ff4757;
            --cyber-neon: 0 0 10px var(--cyber-primary), 0 0 20px var(--cyber-primary), 0 0 30px var(--cyber-primary);
        }
        
        body {
            font-family: 'Exo 2', sans-serif;
            background: var(--cyber-dark);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(0, 245, 255, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(255, 0, 255, 0.1) 0%, transparent 20%);
            color: var(--cyber-text);
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }
        
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
            background: var(--cyber-card);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 3rem;
            border: 1px solid var(--cyber-border);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }
        
        .error-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--cyber-primary), var(--cyber-secondary));
            box-shadow: var(--cyber-neon);
        }
        
        .error-code {
            font-size: 6rem;
            font-weight: 800;
            font-family: 'Orbitron', monospace;
            background: linear-gradient(45deg, var(--cyber-accent), var(--cyber-success));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: var(--cyber-neon);
            margin-bottom: 1rem;
            animation: cyberGlow 3s ease-in-out infinite alternate;
        }
        
        @keyframes cyberGlow {
            0% { text-shadow: var(--cyber-neon); }
            100% { text-shadow: 0 0 20px var(--cyber-accent), 0 0 40px var(--cyber-accent), 0 0 60px var(--cyber-accent); }
        }
        
        .error-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--cyber-text);
            font-family: 'Orbitron', monospace;
        }
        
        .error-message {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            color: var(--cyber-text-secondary);
            line-height: 1.6;
        }
        
        .suggestions {
            margin: 2rem 0;
            text-align: left;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid var(--cyber-border);
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
            background: rgba(0, 245, 255, 0.1);
            transform: translateX(5px);
            border-color: var(--cyber-primary);
            box-shadow: 0 0 15px rgba(0, 245, 255, 0.3);
        }
        
        .suggestion-link {
            color: var(--cyber-success);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        
        .suggestion-link:hover {
            color: var(--cyber-accent);
        }
        
        .suggestion-type {
            background: rgba(0, 255, 157, 0.2);
            padding: 2px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            color: var(--cyber-success);
            border: 1px solid rgba(0, 255, 157, 0.3);
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
            position: relative;
            overflow: hidden;
            font-family: 'Exo 2', sans-serif;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--cyber-accent), var(--cyber-secondary));
            color: white;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.6);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--cyber-success);
            border-color: var(--cyber-success);
        }
        
        .btn-secondary:hover {
            background: var(--cyber-success);
            color: var(--cyber-dark);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 255, 157, 0.4);
        }
        
        .search-box {
            margin: 2rem 0;
        }
        
        .search-input {
            width: 100%;
            padding: 15px;
            border-radius: 50px;
            border: 2px solid var(--cyber-border);
            background: rgba(0, 0, 0, 0.3);
            color: var(--cyber-text);
            font-size: 1.1rem;
            outline: none;
            transition: all 0.3s ease;
            font-family: 'Exo 2', sans-serif;
        }
        
        .search-input:focus {
            border-color: var(--cyber-primary);
            box-shadow: 0 0 20px rgba(0, 245, 255, 0.3);
            background: rgba(0, 0, 0, 0.5);
        }
        
        .search-input::placeholder {
            color: var(--cyber-text-secondary);
        }
        
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
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