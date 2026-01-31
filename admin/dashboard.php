<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}

$current_user = get_user_data(get_current_user_id());
if ($current_user['username'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Get dashboard statistics
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_posts' => $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'total_comments' => $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
    'pending_reports' => $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn(),
    'active_users_today' => $pdo->query("SELECT COUNT(*) FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 1 DAY)")->fetchColumn(),
    'new_users_week' => $pdo->query("SELECT COUNT(*) FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn()
];

// Get recent activity
$recent_posts = $pdo->query("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shield-alt"></i> ADMIN PANEL</h2>
                <span class="version">V3.0</span>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>User Management</span>
                </a>
                <a href="posts.php" class="nav-item">
                    <i class="fas fa-comments"></i>
                    <span>Content Management</span>
                </a>
                <a href="categories.php" class="nav-item">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-flag"></i>
                    <span>Reports</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="moderation.php" class="nav-item">
                    <i class="fas fa-gavel"></i>
                    <span>Moderation</span>
                </a>
                <a href="analytics.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>
                <a href="../index.php" class="nav-item">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Site</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <div class="header-content">
                    <h1><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h1>
                    <div class="admin-info">
                        <span>Welcome, <?php echo htmlspecialchars($current_user['username']); ?>!</span>
                        <a href="../logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </header>

            <!-- Stats Cards -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p>Total Users</p>
                        <span class="trend positive">+<?php echo $stats['new_users_week']; ?> this week</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon posts">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_posts']); ?></h3>
                        <p>Total Posts</p>
                        <span class="trend neutral">Active community</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon comments">
                        <i class="fas fa-comment"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_comments']); ?></h3>
                        <p>Total Comments</p>
                        <span class="trend positive">Growing engagement</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon reports">
                        <i class="fas fa-flag"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['pending_reports']; ?></h3>
                        <p>Pending Reports</p>
                        <span class="trend warning"><?php echo $stats['pending_reports'] > 0 ? 'Action required' : 'All clear'; ?></span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon active">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['active_users_today']; ?></h3>
                        <p>Active Today</p>
                        <span class="trend positive">Good engagement</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon growth">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo round(($stats['active_users_today'] / max($stats['total_users'], 1)) * 100, 1); ?>%</h3>
                        <p>Engagement Rate</p>
                        <span class="trend positive">Healthy growth</span>
                    </div>
                </div>
            </section>

            <!-- Recent Activity -->
            <div class="activity-grid">
                <!-- Recent Posts -->
                <div class="activity-panel">
                    <div class="panel-header">
                        <h3><i class="fas fa-history"></i> Recent Posts</h3>
                        <a href="posts.php" class="view-all">View All</a>
                    </div>
                    <div class="panel-content">
                        <?php if (empty($recent_posts)): ?>
                            <p class="no-data">No posts yet</p>
                        <?php else: ?>
                            <?php foreach($recent_posts as $post): ?>
                                <div class="activity-item">
                                    <div class="item-content">
                                        <h4><a href="../post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a></h4>
                                        <p>By <?php echo htmlspecialchars($post['username']); ?></p>
                                        <small><?php echo time_elapsed_string($post['created_at']); ?></small>
                                    </div>
                                    <div class="item-actions">
                                        <span class="score"><?php echo $post['score']; ?> EXP</span>
                                        <div class="quick-actions">
                                            <button class="action-btn edit" onclick="editPost(<?php echo $post['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn delete" onclick="deletePost(<?php echo $post['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="activity-panel">
                    <div class="panel-header">
                        <h3><i class="fas fa-user-plus"></i> New Users</h3>
                        <a href="users.php" class="view-all">View All</a>
                    </div>
                    <div class="panel-content">
                        <?php if (empty($recent_users)): ?>
                            <p class="no-data">No users yet</p>
                        <?php else: ?>
                            <?php foreach($recent_users as $user): ?>
                                <div class="activity-item">
                                    <div class="user-avatar">
                                        <img src="<?php echo $user['avatar'] ?: '../assets/images/default-avatar.png'; ?>" alt="Avatar">
                                    </div>
                                    <div class="item-content">
                                        <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                                        <p><?php echo format_number($user['exp']); ?> EXP</p>
                                        <small>Joined <?php echo time_elapsed_string($user['created_at']); ?></small>
                                    </div>
                                    <div class="item-actions">
                                        <span class="user-level"><?php echo get_user_level($user['exp']); ?></span>
                                        <div class="quick-actions">
                                            <button class="action-btn view" onclick="viewUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn ban" onclick="banUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <section class="quick-actions-section">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="actions-grid">
                    <button class="quick-action-btn primary" onclick="window.location='users.php'">
                        <i class="fas fa-user-plus"></i>
                        <span>Manage Users</span>
                    </button>
                    <button class="quick-action-btn secondary" onclick="window.location='posts.php'">
                        <i class="fas fa-comment-medical"></i>
                        <span>Moderate Content</span>
                    </button>
                    <button class="quick-action-btn success" onclick="window.location='reports.php'">
                        <i class="fas fa-flag"></i>
                        <span>Handle Reports</span>
                    </button>
                    <button class="quick-action-btn warning" onclick="window.location='settings.php'">
                        <i class="fas fa-cog"></i>
                        <span>Site Settings</span>
                    </button>
                    <button class="quick-action-btn info" onclick="window.location='analytics.php'">
                        <i class="fas fa-chart-pie"></i>
                        <span>View Analytics</span>
                    </button>
                    <button class="quick-action-btn danger" onclick="backupDatabase()">
                        <i class="fas fa-download"></i>
                        <span>Backup Database</span>
                    </button>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Admin dashboard JavaScript
        function editPost(postId) {
            window.location = `edit-post.php?id=${postId}`;
        }

        function deletePost(postId) {
            if (confirm('Are you sure you want to delete this post?')) {
                // Implement delete functionality
                fetch('../ajax/delete-post.php', {
                    method: 'POST',
                    body: new FormData(Object.assign(document.createElement('form'), {
                        innerHTML: `<input name="post_id" value="${postId}">`
                    }))
                }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting post: ' + data.message);
                    }
                });
            }
        }

        function viewUser(userId) {
            window.location = `user-details.php?id=${userId}`;
        }

        function banUser(userId) {
            if (confirm('Are you sure you want to ban this user?')) {
                // Implement ban functionality
                fetch('../ajax/ban-user.php', {
                    method: 'POST',
                    body: new FormData(Object.assign(document.createElement('form'), {
                        innerHTML: `<input name="user_id" value="${userId}">`
                    }))
                }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error banning user: ' + data.message);
                    }
                });
            }
        }

        function backupDatabase() {
            if (confirm('Start database backup? This may take a few moments.')) {
                window.location = 'backup.php';
            }
        }

        // Auto-refresh dashboard data
        setInterval(() => {
            // Could implement real-time updates here
        }, 30000); // Refresh every 30 seconds
    </script>
</body>
</html>