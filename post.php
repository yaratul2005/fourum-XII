<?php
require_once 'config.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$post_id) {
    header('Location: index.php');
    exit();
}

// Get post data
$stmt = $pdo->prepare("SELECT p.*, u.username, u.exp, u.avatar,
                      (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id AND c.status = 'active') as comment_count
                      FROM posts p 
                      JOIN users u ON p.user_id = u.id 
                      WHERE p.id = ? AND p.status = 'active'");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header('Location: index.php');
    exit();
}

// Increment view count
$stmt = $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
$stmt->execute([$post_id]);

// Get comments
$stmt = $pdo->prepare("SELECT c.*, u.username, u.exp, u.avatar,
                      (SELECT COUNT(*) FROM comment_votes cv WHERE cv.comment_id = c.id AND cv.vote_type = 'up') -
                      (SELECT COUNT(*) FROM comment_votes cv WHERE cv.comment_id = c.id AND cv.vote_type = 'down') as score
                      FROM comments c 
                      JOIN users u ON c.user_id = u.id 
                      WHERE c.post_id = ? AND c.status = 'active' 
                      ORDER BY score DESC, c.created_at ASC");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for navigation
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's vote on this post (if logged in)
$user_vote = null;
if (is_logged_in()) {
    $stmt = $pdo->prepare("SELECT vote_type FROM post_votes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([get_current_user_id(), $post_id]);
    $user_vote = $stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="cyber-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1><i class="fas fa-robot"></i> FUROM</h1>
                    <span class="tagline">Futuristic Community Platform</span>
                </div>
                
                <nav class="main-nav">
                    <a href="index.php" class="nav-link">Home</a>
                    <?php foreach($categories as $cat): ?>
                        <a href="index.php?category=<?php echo $cat['name']; ?>" class="nav-link">
                            <i class="fas fa-<?php echo $cat['icon']; ?>"></i>
                            <?php echo ucfirst($cat['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
                
                <div class="user-actions">
                    <?php if(is_logged_in()): ?>
                        <?php $current_user = get_user_data(get_current_user_id()); ?>
                        <div class="user-dropdown">
                            <button class="user-btn">
                                <img src="<?php echo $current_user['avatar'] ?: 'assets/images/default-avatar.png'; ?>" 
                                     alt="Avatar" class="avatar-small">
                                <span class="username"><?php echo $current_user['username']; ?></span>
                                <span class="exp-badge"><?php echo format_number($current_user['exp']); ?> EXP</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                                <a href="create-post.php"><i class="fas fa-plus"></i> Create Post</a>
                                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline">Login</a>
                        <a href="register.php" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        <div class="container">
            <div class="content-wrapper">
                <!-- Main Post Content -->
                <section class="main-feed" style="grid-column: 1 / -1;">
                    <!-- Post Card -->
                    <article class="post-card" style="margin-bottom: 2rem;">
                        <div class="post-vote">
                            <button class="vote-btn upvote <?php echo $user_vote === 'up' ? 'active' : ''; ?>" 
                                    data-post-id="<?php echo $post['id']; ?>">
                                <i class="fas fa-arrow-up"></i>
                            </button>
                            <span class="vote-count"><?php echo $post['score']; ?></span>
                            <button class="vote-btn downvote <?php echo $user_vote === 'down' ? 'active' : ''; ?>" 
                                    data-post-id="<?php echo $post['id']; ?>">
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
                                        <img src="<?php echo $post['avatar'] ?: 'assets/images/default-avatar.png'; ?>" 
                                             alt="Avatar" class="avatar-xs">
                                        u/<?php echo htmlspecialchars($post['username']); ?>
                                    </span>
                                    <span class="user-level"><?php echo get_user_level($post['exp']); ?></span>
                                    <span class="post-time"><?php echo time_elapsed_string($post['created_at']); ?></span>
                                    <span class="post-views"><i class="fas fa-eye"></i> <?php echo format_number($post['views']); ?> views</span>
                                </div>
                            </div>
                            
                            <h1 class="post-title" style="font-size: 2rem; margin: 1rem 0;">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </h1>
                            
                            <div class="post-body" style="color: var(--text-secondary); line-height: 1.8; margin: 1.5rem 0; white-space: pre-wrap;">
                                <?php echo htmlspecialchars($post['content']); ?>
                            </div>
                            
                            <div class="post-footer">
                                <div style="display: flex; gap: 1rem; align-items: center;">
                                    <a href="#comments" class="comment-link">
                                        <i class="fas fa-comment"></i>
                                        <?php echo $post['comment_count']; ?> comments
                                    </a>
                                    <button class="action-btn share-btn" data-url="post.php?id=<?php echo $post['id']; ?>">
                                        <i class="fas fa-share"></i> Share
                                    </button>
                                    <?php if(is_logged_in() && get_current_user_id() == $post['user_id']): ?>
                                        <button class="action-btn">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if(is_logged_in()): ?>
                                    <button class="btn btn-primary" onclick="document.getElementById('comment-form').scrollIntoView({behavior: 'smooth'});">
                                        <i class="fas fa-comment"></i> Add Comment
                                    </button>
                                <?php else: ?>
                                    <a href="login.php?redirect=post.php?id=<?php echo $post['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt"></i> Login to Comment
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>

                    <!-- Comments Section -->
                    <div id="comments">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h2><i class="fas fa-comments"></i> Comments (<?php echo count($comments); ?>)</h2>
                            <?php if(!empty($comments)): ?>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button class="btn btn-outline" id="sort-best">
                                        <i class="fas fa-sort-amount-down"></i> Best
                                    </button>
                                    <button class="btn btn-outline" id="sort-new">
                                        <i class="fas fa-clock"></i> New
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Comment Form -->
                        <?php if(is_logged_in()): ?>
                            <div class="comment-form-container" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 15px; padding: 1.5rem; margin-bottom: 2rem;">
                                <h3><i class="fas fa-comment-dots"></i> Add a Comment</h3>
                                <form id="comment-form" style="margin-top: 1rem;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <div class="form-group">
                                        <textarea name="content" class="form-textarea" 
                                                  placeholder="Share your thoughts..." 
                                                  required minlength="1" style="min-height: 100px;"></textarea>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                                        <small style="color: var(--text-secondary);">
                                            <i class="fas fa-bolt"></i> Earn <?php echo EXP_COMMENT; ?> EXP for commenting
                                        </small>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Post Comment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>

                        <!-- Comments List -->
                        <div class="comments-container">
                            <?php if(empty($comments)): ?>
                                <div class="empty-state" style="text-align: center; padding: 3rem;">
                                    <i class="fas fa-comment-slash fa-3x" style="color: var(--text-secondary); margin-bottom: 1rem;"></i>
                                    <h3>No comments yet</h3>
                                    <p style="color: var(--text-secondary);">Be the first to share your thoughts on this post!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($comments as $comment): ?>
                                    <div class="comment-card" data-comment-id="<?php echo $comment['id']; ?>" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 10px; padding: 1.5rem; margin-bottom: 1rem;">
                                        <div style="display: flex; gap: 1rem;">
                                            <div class="comment-vote" style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem; min-width: 40px;">
                                                <button class="vote-btn upvote" data-comment-id="<?php echo $comment['id']; ?>">
                                                    <i class="fas fa-arrow-up"></i>
                                                </button>
                                                <span class="vote-count" style="font-weight: 600;"><?php echo $comment['score']; ?></span>
                                                <button class="vote-btn downvote" data-comment-id="<?php echo $comment['id']; ?>">
                                                    <i class="fas fa-arrow-down"></i>
                                                </button>
                                            </div>
                                            
                                            <div style="flex: 1;">
                                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                                    <img src="<?php echo $comment['avatar'] ?: 'assets/images/default-avatar.png'; ?>" 
                                                         alt="Avatar" class="avatar-xs">
                                                    <span style="color: var(--text-primary); font-weight: 500;">
                                                        u/<?php echo htmlspecialchars($comment['username']); ?>
                                                    </span>
                                                    <span class="user-level" style="font-size: 0.7rem; padding: 0.1rem 0.5rem;">
                                                        <?php echo get_user_level($comment['exp']); ?>
                                                    </span>
                                                    <span style="color: var(--text-secondary); font-size: 0.9rem;">
                                                        <?php echo time_elapsed_string($comment['created_at']); ?>
                                                    </span>
                                                </div>
                                                
                                                <div style="color: var(--text-secondary); line-height: 1.6; white-space: pre-wrap;">
                                                    <?php echo htmlspecialchars($comment['content']); ?>
                                                </div>
                                                
                                                <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                                                    <button class="action-btn" onclick="replyToComment(<?php echo $comment['id']; ?>)">
                                                        <i class="fas fa-reply"></i> Reply
                                                    </button>
                                                    <button class="action-btn">
                                                        <i class="fas fa-share"></i> Share
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="cyber-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-robot"></i> FUROM</h3>
                    <p>The next-generation community platform.</p>
                </div>
                <div class="footer-section">
                    <h4>Navigation</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="rules.php">Rules</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Furom. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
        // Comment submission
        document.getElementById('comment-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<div class="loading"></div> Posting...';
            submitBtn.disabled = true;
            
            fetch('ajax/create-comment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Comment posted successfully!', 'success');
                    // Reload page to show new comment
                    location.reload();
                } else {
                    showNotification(data.message || 'Failed to post comment', 'error');
                }
            })
            .catch(error => {
                showNotification('Network error occurred', 'error');
                console.error('Comment error:', error);
            })
            .finally(() => {
                // Restore button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        
        // Comment sorting
        document.getElementById('sort-best')?.addEventListener('click', function() {
            // Sort by score (default behavior)
            location.hash = '#comments';
        });
        
        document.getElementById('sort-new')?.addEventListener('click', function() {
            // Would need to implement server-side sorting
            showNotification('Sorting by newest - feature coming soon!', 'info');
        });
        
        // Reply functionality
        function replyToComment(commentId) {
            const commentForm = document.getElementById('comment-form');
            const textarea = commentForm.querySelector('textarea[name="content"]');
            
            // Add mention to textarea
            const currentContent = textarea.value;
            const mention = `@comment-${commentId} `;
            
            if (!currentContent.includes(mention)) {
                textarea.value = mention + currentContent;
            }
            
            // Focus and scroll to form
            textarea.focus();
            commentForm.scrollIntoView({behavior: 'smooth'});
        }
        
        // Initialize comment voting
        document.addEventListener('DOMContentLoaded', function() {
            document.dispatchEvent(new CustomEvent('commentsLoaded'));
        });
    </script>
</body>
</html>