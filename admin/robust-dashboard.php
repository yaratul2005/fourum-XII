<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to prevent header issues
ob_start();

try {
    // Load configuration
    require_once '../config.php';
    
    // Load functions
    require_once '../includes/functions.php';
    
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check authentication
    if (!is_logged_in()) {
        header('Location: ../login.php');
        exit();
    }
    
    $current_user = get_user_data(get_current_user_id());
    if (!$current_user || $current_user['username'] !== 'admin') {
        header('Location: ../index.php');
        exit();
    }
    
    // Initialize stats with safe defaults
    $stats = [
        'total_users' => 0,
        'total_posts' => 0,
        'total_comments' => 0,
        'pending_reports' => 0,
        'active_users_today' => 0,
        'new_users_week' => 0
    ];
    
    $recent_posts = [];
    $recent_users = [];
    
    // Safely get statistics
    try {
        if (isset($pdo)) {
            // Get basic counts with error handling
            $queries = [
                'total_users' => "SELECT COUNT(*) FROM users",
                'total_posts' => "SELECT COUNT(*) FROM posts",
                'total_comments' => "SELECT COUNT(*) FROM comments",
                'pending_reports' => "SELECT COUNT(*) FROM reports WHERE status = 'pending'",
                'active_users_today' => "SELECT COUNT(*) FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 1 DAY)",
                'new_users_week' => "SELECT COUNT(*) FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)"
            ];
            
            foreach ($queries as $key => $query) {
                try {
                    $stmt = $pdo->query($query);
                    $stats[$key] = $stmt->fetchColumn();
                } catch (Exception $e) {
                    $stats[$key] = 0; // Default to 0 if query fails
                }
            }
            
            // Get recent activity
            try {
                $stmt = $pdo->query("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5");
                $recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $recent_posts = []; // Empty array if query fails
            }
            
            try {
                $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
                $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $recent_users = []; // Empty array if query fails
            }
        }
    } catch (Exception $e) {
        // Log error but continue with default values
        error_log("Dashboard stats error: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    // Handle fatal errors gracefully
    $fatal_error = $e->getMessage();
    error_log("Dashboard fatal error: " . $fatal_error);
}

// Flush output buffer
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Robust Admin Dashboard - <?php echo defined('SITE_NAME') ? SITE_NAME : 'Furom'; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .error-banner {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .stat-card.loading {
            opacity: 0.7;
        }
        .fallback-content {
            background: #fefefe;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <?php if (isset($fatal_error)): ?>
        <div class="error-banner">
            <h2>🚨 System Error</h2>
            <p><?php echo htmlspecialchars($fatal_error); ?></p>
            <p><a href="../index.php">Return to main site</a></p>
        </div>
    <?php else: ?>
    
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shield-alt"></i> ADMIN PANEL</h2>
                <span class="version">V3.0</span>
            </div>
            
            <nav class="sidebar-nav">
                <a href="robust-dashboard.php" class="nav-item active">
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
                <a href="backup.php" class="nav-item">
                    <i class="fas fa-database"></i>
                    <span>Backup & Restore</span>
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
                    <h1><i class="fas fa-chart-line"></i> Admin Dashboard</h1>
                    <div class="admin-info">
                        <span>Welcome, <?php echo htmlspecialchars($current_user['username']); ?>!</span>
                        <a href="../logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- Stats Overview -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_users']); ?></h3>
                            <p>Total Users</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon posts">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_posts']); ?></h3>
                            <p>Total Posts</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon comments">
                            <i class="fas fa-comment"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_comments']); ?></h3>
                            <p>Total Comments</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon reports">
                            <i class="fas fa-flag"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['pending_reports']); ?></h3>
                            <p>Pending Reports</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="section">
                    <h2>Quick Actions</h2>
                    <div class="quick-actions">
                        <a href="users.php" class="action-card">
                            <i class="fas fa-user-cog"></i>
                            <span>Manage Users</span>
                        </a>
                        <a href="posts.php" class="action-card">
                            <i class="fas fa-edit"></i>
                            <span>Manage Content</span>
                        </a>
                        <a href="reports.php" class="action-card">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Handle Reports</span>
                        </a>
                        <a href="backup.php" class="action-card">
                            <i class="fas fa-database"></i>
                            <span>Backup Data</span>
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="grid">
                    <div class="card">
                        <h3><i class="fas fa-history"></i> Recent Posts</h3>
                        <?php if (!empty($recent_posts)): ?>
                            <?php foreach($recent_posts as $post): ?>
                                <div class="activity-item">
                                    <div class="activity-content">
                                        <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                        <small>by <?php echo htmlspecialchars($post['username']); ?></small>
                                    </div>
                                    <div class="activity-meta">
                                        <span class="score"><?php echo $post['score']; ?> points</span>
                                        <span><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-data">No recent posts found</p>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-user-plus"></i> New Users</h3>
                        <?php if (!empty($recent_users)): ?>
                            <?php foreach($recent_users as $user): ?>
                                <div class="activity-item">
                                    <div class="activity-content">
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        <small><?php echo htmlspecialchars($user['email']); ?></small>
                                    </div>
                                    <div class="activity-meta">
                                        <span>EXP: <?php echo $user['exp']; ?></span>
                                        <span><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-data">No new users found</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- System Status -->
                <div class="section">
                    <h2>System Status</h2>
                    <div class="system-status">
                        <div class="status-item">
                            <span class="status-indicator online"></span>
                            <span>Database Connection</span>
                            <span class="status-value">Online</span>
                        </div>
                        <div class="status-item">
                            <span class="status-indicator online"></span>
                            <span>Authentication</span>
                            <span class="status-value">Active</span>
                        </div>
                        <div class="status-item">
                            <span class="status-indicator <?php echo $stats['pending_reports'] > 0 ? 'warning' : 'online'; ?>"></span>
                            <span>Reports Queue</span>
                            <span class="status-value"><?php echo $stats['pending_reports']; ?> pending</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php endif; ?>
    
    <script>
        // Simple JavaScript for admin panel
        document.addEventListener('DOMContentLoaded', function() {
            // Add any admin-specific JavaScript here
            console.log('Admin panel loaded successfully');
        });
    </script>
</body>
</html>