<?php
require_once 'config.php';
redirect_if_not_logged_in();

$user_id = get_current_user_id();
$user_data = get_user_data($user_id);

// Get user's posts
$stmt = $pdo->prepare("SELECT p.*, 
                      (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) as comment_count
                      FROM posts p 
                      WHERE p.user_id = ? AND p.status = 'active' 
                      ORDER BY p.created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$user_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's comments
$stmt = $pdo->prepare("SELECT c.*, p.title as post_title, p.id as post_id
                      FROM comments c 
                      JOIN posts p ON c.post_id = p.id
                      WHERE c.user_id = ? AND c.status = 'active'
                      ORDER BY c.created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$user_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get EXP statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total_posts FROM posts WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_posts = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as total_comments FROM comments WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_comments = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(amount) as total_exp_gained FROM exp_log WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_exp_gained = $stmt->fetchColumn() ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user_data['username']); ?>'s Profile - <?php echo SITE_NAME; ?></title>
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
                </nav>
                <div class="user-actions">
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <img src="<?php echo $user_data['avatar'] ?: 'assets/images/default-avatar.png'; ?>" 
                                 alt="Avatar" class="avatar-small">
                            <span class="username"><?php echo $user_data['username']; ?></span>
                            <span class="exp-badge"><?php echo format_number($user_data['exp']); ?> EXP</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="create-post.php"><i class="fas fa-plus"></i> Create Post</a>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        <div class="container">
            <!-- Profile Header -->
            <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 15px; padding: 2rem; margin-bottom: 2rem;">
                <div style="display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;">
                    <img src="<?php echo $user_data['avatar'] ?: 'assets/images/default-avatar.png'; ?>" 
                         alt="Profile Avatar" 
                         style="width: 120px; height: 120px; border-radius: 50%; border: 3px solid var(--primary); object-fit: cover;">
                    
                    <div style="flex: 1;">
                        <h1 style="font-family: 'Orbitron', monospace; color: var(--primary); margin-bottom: 0.5rem;">
                            u/<?php echo htmlspecialchars($user_data['username']); ?>
                        </h1>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
                            <span style="background: linear-gradient(45deg, var(--secondary), var(--accent)); padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; color: white;">
                                <?php echo get_user_level($user_data['exp']); ?>
                            </span>
                            <span style="background: rgba(0, 245, 255, 0.2); padding: 0.5rem 1rem; border-radius: 20px; color: var(--primary);">
                                <i class="fas fa-bolt"></i> <?php echo format_number($user_data['exp']); ?> EXP
                            </span>
                        </div>
                        
                        <?php if ($user_data['bio']): ?>
                            <p style="color: var(--text-secondary); line-height: 1.6; max-width: 600px;">
                                <?php echo htmlspecialchars($user_data['bio']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <div style="display: flex; gap: 2rem; margin-top: 1rem; color: var(--text-secondary); flex-wrap: wrap;">
                            <span><i class="fas fa-calendar-alt"></i> Joined <?php echo date('M Y', strtotime($user_data['created_at'])); ?></span>
                            <span><i class="fas fa-sign-in-alt"></i> Last seen <?php echo time_elapsed_string($user_data['last_login'] ?? $user_data['created_at']); ?></span>
                            <?php if ($user_data['verified']): ?>
                                <span style="color: var(--success);"><i class="fas fa-badge-check"></i> Verified</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div style="text-align: center; min-width: 200px;">
                        <button class="btn btn-outline" onclick="showEditModal()">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                        <div style="margin-top: 1rem; padding: 1rem; background: rgba(0, 0, 0, 0.2); border-radius: 10px;">
                            <h4 style="color: var(--primary); margin-bottom: 0.5rem;">Achievements</h4>
                            <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                                <?php if ($total_posts >= 1): ?>
                                    <i class="fas fa-first-post" title="First Post" style="color: var(--success);"></i>
                                <?php endif; ?>
                                <?php if ($user_data['exp'] >= 100): ?>
                                    <i class="fas fa-medal" title="Level 2 Achieved" style="color: var(--warning);"></i>
                                <?php endif; ?>
                                <?php if ($total_comments >= 10): ?>
                                    <i class="fas fa-comments" title="10+ Comments" style="color: var(--primary);"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 15px; padding: 1.5rem; text-align: center;">
                    <i class="fas fa-file-alt fa-2x" style="color: var(--primary); margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo $total_posts; ?></h3>
                    <p style="color: var(--text-secondary);">Posts Created</p>
                </div>
                
                <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 15px; padding: 1.5rem; text-align: center;">
                    <i class="fas fa-comment fa-2x" style="color: var(--secondary); margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo $total_comments; ?></h3>
                    <p style="color: var(--text-secondary);">Comments Made</p>
                </div>
                
                <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 15px; padding: 1.5rem; text-align: center;">
                    <i class="fas fa-chart-line fa-2x" style="color: var(--accent); margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo format_number($total_exp_gained); ?></h3>
                    <p style="color: var(--text-secondary);">Total EXP Gained</p>
                </div>
                
                <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 15px; padding: 1.5rem; text-align: center;">
                    <i class="fas fa-trophy fa-2x" style="color: var(--warning); margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo get_user_level($user_data['exp']); ?></h3>
                    <p style="color: var(--text-secondary);">Current Level</p>
                </div>
            </div>

            <!-- Recent Activity Tabs -->
            <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 15px; overflow: hidden;">
                <div style="display: flex; border-bottom: 1px solid var(--border-color);">
                    <button class="tab-btn active" data-tab="posts" style="flex: 1; padding: 1rem; background: transparent; border: none; color: var(--primary); font-weight: 500; cursor: pointer;">
                        <i class="fas fa-file-alt"></i> Recent Posts
                    </button>
                    <button class="tab-btn" data-tab="comments" style="flex: 1; padding: 1rem; background: transparent; border: none; color: var(--text-secondary); font-weight: 500; cursor: pointer;">
                        <i class="fas fa-comment"></i> Recent Comments
                    </button>
                    <button class="tab-btn" data-tab="exp" style="flex: 1; padding: 1rem; background: transparent; border: none; color: var(--text-secondary); font-weight: 500; cursor: pointer;">
                        <i class="fas fa-history"></i> EXP History
                    </button>
                </div>
                
                <!-- Posts Tab -->
                <div class="tab-content active" id="posts-tab" style="padding: 1.5rem;">
                    <?php if (empty($user_posts)): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt fa-3x"></i>
                            <h3>No posts yet</h3>
                            <p>Start sharing your thoughts with the community!</p>
                            <a href="create-post.php" class="btn btn-primary">Create Your First Post</a>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach($user_posts as $post): ?>
                                <div style="background: rgba(255, 255, 255, 0.05); border-radius: 10px; padding: 1rem; border-left: 3px solid var(--primary);">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                        <h4 style="color: var(--text-primary); margin: 0;">
                                            <a href="post.php?id=<?php echo $post['id']; ?>" style="color: inherit; text-decoration: none;">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                        </h4>
                                        <span style="color: var(--text-secondary); font-size: 0.9rem;">
                                            <?php echo time_elapsed_string($post['created_at']); ?>
                                        </span>
                                    </div>
                                    <div style="display: flex; gap: 1rem; color: var(--text-secondary); font-size: 0.9rem;">
                                        <span><i class="fas fa-arrow-up"></i> <?php echo $post['score']; ?> votes</span>
                                        <span><i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?> comments</span>
                                        <span><i class="fas fa-eye"></i> <?php echo format_number($post['views']); ?> views</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Comments Tab -->
                <div class="tab-content" id="comments-tab" style="padding: 1.5rem; display: none;">
                    <?php if (empty($user_comments)): ?>
                        <div class="empty-state">
                            <i class="fas fa-comment fa-3x"></i>
                            <h3>No comments yet</h3>
                            <p>Join discussions by commenting on posts!</p>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach($user_comments as $comment): ?>
                                <div style="background: rgba(255, 255, 255, 0.05); border-radius: 10px; padding: 1rem; border-left: 3px solid var(--secondary);">
                                    <div style="margin-bottom: 0.5rem;">
                                        <a href="post.php?id=<?php echo $comment['post_id']; ?>" style="color: var(--primary); text-decoration: none; font-weight: 500;">
                                            <i class="fas fa-reply"></i> Re: <?php echo htmlspecialchars($comment['post_title']); ?>
                                        </a>
                                    </div>
                                    <p style="color: var(--text-secondary); margin: 0.5rem 0; line-height: 1.6;">
                                        <?php echo htmlspecialchars(substr($comment['content'], 0, 200)); ?>...
                                    </p>
                                    <div style="color: var(--text-secondary); font-size: 0.9rem;">
                                        <?php echo time_elapsed_string($comment['created_at']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- EXP History Tab -->
                <div class="tab-content" id="exp-tab" style="padding: 1.5rem; display: none;">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM exp_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
                    $stmt->execute([$user_id]);
                    $exp_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <?php if (empty($exp_history)): ?>
                        <div class="empty-state">
                            <i class="fas fa-history fa-3x"></i>
                            <h3>No EXP history yet</h3>
                            <p>Earn EXP by participating in the community!</p>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php foreach($exp_history as $record): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border-radius: 8px;">
                                    <div>
                                        <span style="color: var(--text-primary); font-weight: 500;"><?php echo htmlspecialchars($record['reason']); ?></span>
                                        <br>
                                        <span style="color: var(--text-secondary); font-size: 0.9rem;"><?php echo time_elapsed_string($record['created_at']); ?></span>
                                    </div>
                                    <span style="font-weight: 600; font-size: 1.1rem; <?php echo $record['amount'] > 0 ? 'color: var(--success)' : 'color: var(--danger)'; ?>">
                                        <?php echo ($record['amount'] > 0 ? '+' : '') . $record['amount']; ?> EXP
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Profile Modal -->
    <div id="edit-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 15px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; position: relative;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3><i class="fas fa-edit"></i> Edit Profile</h3>
                <button onclick="closeEditModal()" style="background: none; border: none; color: var(--text-primary); font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form id="edit-profile-form" style="padding: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Bio</label>
                    <textarea name="bio" class="form-textarea" placeholder="Tell us about yourself..." maxlength="500"><?php echo htmlspecialchars($user_data['bio'] ?? ''); ?></textarea>
                    <small style="color: var(--text-secondary); font-size: 0.8rem;">Maximum 500 characters</small>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="cyber-footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Furom. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons and tabs
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Show corresponding tab
                const tabId = this.dataset.tab + '-tab';
                document.getElementById(tabId).classList.add('active');
                document.getElementById(tabId).style.display = 'block';
            });
        });
        
        // Edit profile modal
        function showEditModal() {
            document.getElementById('edit-modal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('edit-modal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('edit-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        // Profile update form
        document.getElementById('edit-profile-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<div class="loading"></div> Saving...';
            submitBtn.disabled = true;
            
            fetch('ajax/update-profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Profile updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message || 'Failed to update profile', 'error');
                }
            })
            .catch(error => {
                showNotification('Network error occurred', 'error');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    </script>
</body>
</html>