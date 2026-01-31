<?php
require_once 'config.php';

// Get posts with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$category = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query based on filters
$where_clause = "WHERE p.status = 'active'";
$params = [];

if ($category && $category !== 'all') {
    $where_clause .= " AND p.category = ?";
    $params[] = $category;
}

$query = "SELECT p.*, u.username, u.exp, 
          (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id AND c.status = 'active') as comment_count
          FROM posts p 
          JOIN users u ON p.user_id = u.id 
          $where_clause 
          ORDER BY p.score DESC, p.created_at DESC 
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);

// Bind parameters properly to avoid SQL syntax errors
foreach($params as $key => $param) {
    $stmt->bindValue($key + 1, $param);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total posts count for pagination
$count_query = "SELECT COUNT(*) FROM posts p $where_clause";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_posts = $count_stmt->fetchColumn();
$total_pages = ceil($total_posts / $limit);

// Get categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top users
$stmt = $pdo->query("SELECT id, username, exp FROM users ORDER BY exp DESC LIMIT 5");
$top_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="cyber-header">
        <div class="container">
            <div class="header-content">
                <!-- Site Title and Logo -->
                <div class="site-branding">
                    <div class="logo">
                        <h1><i class="fas fa-robot"></i> FUROM</h1>
                    </div>
                    <div class="site-title">
                        <h2><?php echo htmlspecialchars(SITE_NAME); ?></h2>
                        <p class="tagline">Futuristic Community Platform</p>
                    </div>
                </div>
                
                <!-- Search Box -->
                <div class="search-container">
                    <form method="GET" action="search.php" class="search-form">
                        <div class="search-wrapper">
                            <input type="text" name="q" placeholder="Search discussions..." 
                                   value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                   class="search-input">
                            <button type="submit" class="search-button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Navigation and Actions -->
                <div class="header-actions">
                    <nav class="main-nav">
                        <a href="index.php" class="nav-link active">
                            <i class="fas fa-home"></i> Home
                        </a>
                        <a href="categories.php" class="nav-link">
                            <i class="fas fa-tags"></i> Categories
                        </a>
                        <a href="leaderboard.php" class="nav-link">
                            <i class="fas fa-trophy"></i> Leaderboard
                        </a>
                    </nav>
                    
                    <div class="user-actions">
                        <?php if (is_logged_in()): ?>
                            <a href="create-post.php" class="btn btn-primary post-now-btn">
                                <i class="fas fa-plus"></i> Post Now
                            </a>
                            <div class="user-dropdown">
                                <button class="user-menu-btn">
                                    <img src="<?php echo htmlspecialchars($current_user['avatar'] ?? 'assets/images/default-avatar.png'); ?>" 
                                         alt="Avatar" class="user-avatar">
                                    <span class="username"><?php echo htmlspecialchars($current_user['username']); ?></span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a href="profile.php?id=<?php echo $current_user['id']; ?>">
                                        <i class="fas fa-user"></i> Profile
                                    </a>
                                    <a href="profile-edit.php">
                                        <i class="fas fa-cog"></i> Settings
                                    </a>
                                    <?php if ($current_user['username'] === 'admin'): ?>
                                        <a href="admin/dashboard.php">
                                            <i class="fas fa-shield-alt"></i> Admin Panel
                                        </a>
                                    <?php endif; ?>
                                    <hr>
                                    <a href="logout.php">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="create-post.php" class="btn btn-primary post-now-btn" onclick="handleGuestPost(); return false;">
                                <i class="fas fa-plus"></i> Post Now
                            </a>
                            <a href="login.php" class="btn btn-outline">Login</a>
                            <a href="register.php" class="btn btn-primary">Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        <div class="container">
            <div class="content-wrapper">
                <!-- Sidebar -->
                <aside class="sidebar">
                    <div class="widget">
                        <h3><i class="fas fa-fire"></i> Trending Now</h3>
                        <div class="trending-list">
                            <?php 
                            $trending_stmt = $pdo->query("SELECT * FROM posts WHERE status = 'active' ORDER BY score DESC LIMIT 5");
                            $trending_posts = $trending_stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach($trending_posts as $tpost): ?>
                                <div class="trending-item">
                                    <div class="trending-score"><?php echo $tpost['score']; ?></div>
                                    <div class="trending-content">
                                        <a href="post.php?id=<?php echo $tpost['id']; ?>"><?php echo htmlspecialchars(substr($tpost['title'], 0, 60)); ?>...</a>
                                        <small><?php echo time_elapsed_string($tpost['created_at']); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="widget">
                        <h3><i class="fas fa-trophy"></i> Top Contributors</h3>
                        <div class="leaderboard">
                            <?php foreach($top_users as $index => $user): ?>
                                <div class="leaderboard-item">
                                    <span class="rank">#<?php echo $index + 1; ?></span>
                                    <span class="user-info">
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        <small><?php echo format_number($user['exp']); ?> EXP</small>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </aside>

                <!-- Main Feed -->
                <section class="main-feed">
                    <div class="feed-header">
                        <h2>
                            <?php if($category && $category !== 'all'): ?>
                                <i class="fas fa-<?php echo array_column($categories, 'icon', 'name')[$category] ?? 'folder'; ?>"></i>
                                <?php echo ucfirst($category); ?> Posts
                            <?php else: ?>
                                <i class="fas fa-globe"></i> Latest Discussions
                            <?php endif; ?>
                        </h2>
                        
                        <?php if(is_logged_in()): ?>
                            <a href="create-post.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Post
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Posts List -->
                    <div class="posts-container">
                        <?php if(empty($posts)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox fa-3x"></i>
                                <h3>No posts found</h3>
                                <p><?php echo $category ? 'No posts in this category yet.' : 'Be the first to start a discussion!'; ?></p>
                                <?php if(is_logged_in()): ?>
                                    <a href="create-post.php" class="btn btn-primary">Create First Post</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php foreach($posts as $post): ?>
                                <article class="post-card" data-post-id="<?php echo $post['id']; ?>">
                                    <div class="post-vote">
                                        <button class="vote-btn upvote" data-post-id="<?php echo $post['id']; ?>">
                                            <i class="fas fa-arrow-up"></i>
                                        </button>
                                        <span class="vote-count"><?php echo $post['score']; ?></span>
                                        <button class="vote-btn downvote" data-post-id="<?php echo $post['id']; ?>">
                                            <i class="fas fa-arrow-down"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="post-content">
                                        <div class="post-header">
                                            <div class="post-meta">
                                                <span class="category-badge" style="background: <?php echo array_column($categories, 'color', 'name')[$post['category']] ?? '#007bff'; ?>">
                                                    <?php echo ucfirst($post['category']); ?>
                                                </span>
                                                <span class="post-author">
                                                    <img src="<?php echo get_user_data($post['user_id'])['avatar'] ?: 'assets/images/default-avatar.png'; ?>" 
                                                         alt="Avatar" class="avatar-xs">
                                                    u/<?php echo htmlspecialchars($post['username']); ?>
                                                </span>
                                                <span class="user-level"><?php echo get_user_level($post['exp']); ?></span>
                                                <span class="post-time"><?php echo time_elapsed_string($post['created_at']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <h3 class="post-title">
                                            <a href="post.php?id=<?php echo $post['id']; ?>">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                        </h3>
                                        
                                        <div class="post-excerpt">
                                            <?php echo htmlspecialchars(substr($post['content'], 0, 200)); ?>...
                                        </div>
                                        
                                        <div class="post-footer">
                                            <a href="post.php?id=<?php echo $post['id']; ?>#comments" class="comment-link">
                                                <i class="fas fa-comment"></i>
                                                <?php echo $post['comment_count']; ?> comments
                                            </a>
                                            <div class="post-actions">
                                                <button class="action-btn share-btn" data-url="post.php?id=<?php echo $post['id']; ?>">
                                                    <i class="fas fa-share"></i> Share
                                                </button>
                                                <button class="action-btn save-btn">
                                                    <i class="fas fa-bookmark"></i> Save
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $category ? '&category=' . $category : ''; ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>
                            
                            <div class="page-numbers">
                                <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $category ? '&category=' . $category : ''; ?>" 
                                       class="page-number <?php echo $i == $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                            
                            <?php if($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $category ? '&category=' . $category : ''; ?>" class="page-link">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="cyber-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-robot"></i> FUROM <span class="version-tag">V2.0</span></h3>
                    <p>The next-generation community platform built for the future.</p>
                    <div class="upgrade-notice">
                        <small>✨ Now featuring enhanced animations and super transitions!</small>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="about.php">About</a></li>
                        <li><a href="rules.php">Community Rules</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                        <li><a href="changelog.php">What's New (V2)</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Connect</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-discord"></i></a>
                        <a href="#"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Furom. All rights reserved. | Made with <i class="fas fa-heart"></i> for the community</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="assets/js/main.js"></script>
    <script>
// Handle guest users trying to post
function handleGuestPost() {
    if (confirm('You need to register an account to post. Would you like to register now?')) {
        window.location.href = 'register.php';
    }
}

// Enhanced search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search-input');
    const searchForm = document.querySelector('.search-form');
    
    // Live search suggestions (basic implementation)
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length > 2) {
            searchTimeout = setTimeout(() => {
                // This would typically call an AJAX endpoint for suggestions
                console.log('Searching for:', query);
            }, 300);
        }
    });
    
    // Submit search on Enter
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchForm.submit();
        }
    });
});
</script>

<style>
.site-branding {
    display: flex;
    align-items: center;
    gap: 15px;
}

.site-branding .logo h1 {
    margin: 0;
    font-size: 1.8rem;
    background: linear-gradient(45deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.site-title h2 {
    margin: 0 0 5px 0;
    font-size: 1.4rem;
    color: var(--text-primary);
}

.site-title .tagline {
    margin: 0;
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.search-container {
    flex: 1;
    max-width: 400px;
    margin: 0 20px;
}

.search-form {
    width: 100%;
}

.search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-input {
    width: 100%;
    padding: 12px 50px 12px 20px;
    border-radius: 50px;
    border: 2px solid var(--border-color);
    background: var(--card-bg);
    color: var(--text-primary);
    font-size: 1rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(0, 245, 255, 0.2);
}

.search-button {
    position: absolute;
    right: 5px;
    background: var(--primary);
    color: var(--dark-bg);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-button:hover {
    background: var(--accent);
    transform: scale(1.1);
}

.post-now-btn {
    background: linear-gradient(45deg, var(--accent), var(--secondary));
    color: white;
    padding: 12px 25px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
}

.post-now-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 20px;
}

.main-nav {
    display: flex;
    gap: 15px;
}

.nav-link {
    color: var(--text-secondary);
    text-decoration: none;
    padding: 10px 15px;
    border-radius: 8px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.nav-link:hover,
.nav-link.active {
    color: var(--primary);
    background: rgba(0, 245, 255, 0.1);
}

.user-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

.btn-outline {
    border: 2px solid var(--primary);
    color: var(--primary);
    background: transparent;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-outline:hover {
    background: var(--primary);
    color: var(--dark-bg);
}

.user-dropdown {
    position: relative;
}

.user-menu-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    padding: 8px 15px;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.user-menu-btn:hover {
    border-color: var(--primary);
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.username {
    color: var(--text-primary);
    font-weight: 500;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 10px 0;
    min-width: 200px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    display: none;
}

.user-dropdown:hover .dropdown-menu {
    display: block;
}

.dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    color: var(--text-primary);
    text-decoration: none;
    transition: all 0.2s ease;
}

.dropdown-menu a:hover {
    background: rgba(0, 245, 255, 0.1);
    color: var(--primary);
}

.dropdown-menu hr {
    margin: 10px 0;
    border: none;
    border-top: 1px solid var(--border-color);
}
</style>
</body>
</html>