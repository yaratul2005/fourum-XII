<?php
// Fixed Admin Dashboard - Works with current database state
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering
ob_start();

try {
    // Load configuration with error handling
    if (!file_exists('../config.php')) {
        throw new Exception('Configuration file not found');
    }
    require_once '../config.php';
    
    // Load functions
    if (!file_exists('../includes/functions.php')) {
        throw new Exception('Functions file not found');
    }
    require_once '../includes/functions.php';
    
    // Ensure session is started
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
    
    // Safe statistics gathering
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
    
    // Gather statistics safely
    if (isset($pdo)) {
        try {
            // Basic counts
            $basic_queries = [
                'total_users' => "SELECT COUNT(*) FROM users",
                'total_posts' => "SELECT COUNT(*) FROM posts",
                'total_comments' => "SELECT COUNT(*) FROM comments"
            ];
            
            foreach ($basic_queries as $key => $query) {
                try {
                    $stmt = $pdo->query($query);
                    $stats[$key] = $stmt->fetchColumn();
                } catch (Exception $e) {
                    $stats[$key] = 0;
                }
            }
            
            // Try advanced queries (may fail if tables missing)
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'");
                $stats['pending_reports'] = $stmt->fetchColumn();
            } catch (Exception $e) {
                $stats['pending_reports'] = 0;
            }
            
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 1 DAY)");
                $stats['active_users_today'] = $stmt->fetchColumn();
            } catch (Exception $e) {
                $stats['active_users_today'] = 0;
            }
            
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
                $stats['new_users_week'] = $stmt->fetchColumn();
            } catch (Exception $e) {
                $stats['new_users_week'] = 0;
            }
            
            // Recent activity
            try {
                $stmt = $pdo->query("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5");
                $recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $recent_posts = [];
            }
            
            try {
                $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
                $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $recent_users = [];
            }
        } catch (Exception $e) {
            error_log("Dashboard data collection error: " . $e->getMessage());
        }
    }
    
} catch (Exception $e) {
    $fatal_error = $e->getMessage();
    error_log("Dashboard fatal error: " . $fatal_error);
}

// End output buffering
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fixed Admin Dashboard - <?php echo defined('SITE_NAME') ? SITE_NAME : 'Furom'; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: #2c3e50; color: white; }
        .sidebar-header { padding: 20px; background: #34495e; text-align: center; }
        .sidebar-nav { padding: 15px 0; }
        .nav-item { display: block; padding: 12px 20px; color: #ecf0f1; text-decoration: none; }
        .nav-item:hover, .nav-item.active { background: #3498db; }
        .admin-main { flex: 1; background: #ecf0f1; }
        .admin-header { background: white; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .admin-content { padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-icon { font-size: 2em; margin-bottom: 10px; }
        .users { color: #3498db; }
        .posts { color: #2ecc71; }
        .comments { color: #f39c12; }
        .reports { color: #e74c3c; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .activity-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .activity-item:last-child { border-bottom: none; }
        .error-banner { background: #fee; border: 1px solid #fcc; color: #c33; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .success-banner { background: #efe; border: 1px solid #cfc; color: #363; padding: 15px; margin: 15px 0; border-radius: 5px; }
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
                <span>V3.0</span>
            </div>
            
            <nav class="sidebar-nav">
                <a href="fixed-dashboard.php" class="nav-item active">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="users.php" class="nav-item">
                    <i class="fas fa-users"></i> User Management
                </a>
                <a href="posts.php" class="nav-item">
                    <i class="fas fa-comments"></i> Content Management
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-flag"></i> Reports
                </a>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="backup.php" class="nav-item">
                    <i class="fas fa-database"></i> Backup
                </a>
                <a href="../index.php" class="nav-item">
                    <i class="fas fa-arrow-left"></i> Back to Site
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1><i class="fas fa-chart-line"></i> Admin Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars($current_user['username']); ?>!</p>
                <a href="../logout.php">Logout</a>
            </header>

            <div class="admin-content">
                <?php if ($stats['pending_reports'] > 0): ?>
                    <div class="success-banner">
                        <h3><i class="fas fa-exclamation-circle"></i> Action Required</h3>
                        <p>You have <?php echo $stats['pending_reports']; ?> pending reports to review.</p>
                        <a href="reports.php">View Reports</a>
                    </div>
                <?php endif; ?>

                <!-- Stats Overview -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p>Total Users</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon posts">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3><?php echo number_format($stats['total_posts']); ?></h3>
                        <p>Total Posts</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon comments">
                            <i class="fas fa-comment"></i>
                        </div>
                        <h3><?php echo number_format($stats['total_comments']); ?></h3>
                        <p>Total Comments</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon reports">
                            <i class="fas fa-flag"></i>
                        </div>
                        <h3><?php echo number_format($stats['pending_reports']); ?></h3>
                        <p>Pending Reports</p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <h2>Quick Actions</h2>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <a href="users.php" style="padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px;">Manage Users</a>
                        <a href="posts.php" style="padding: 10px 20px; background: #2ecc71; color: white; text-decoration: none; border-radius: 5px;">Manage Content</a>
                        <a href="reports.php" style="padding: 10px 20px; background: #f39c12; color: white; text-decoration: none; border-radius: 5px;">Handle Reports</a>
                        <a href="backup.php" style="padding: 10px 20px; background: #e74c3c; color: white; text-decoration: none; border-radius: 5px;">Backup Data</a>
                    </div>
                </div>

                <!-- Recent Activity Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="card">
                        <h3><i class="fas fa-history"></i> Recent Posts</h3>
                        <?php if (!empty($recent_posts)): ?>
                            <?php foreach($recent_posts as $post): ?>
                                <div class="activity-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars(substr($post['title'], 0, 30)); ?>...</strong>
                                        <small>by <?php echo htmlspecialchars($post['username']); ?></small>
                                    </div>
                                    <div>
                                        <span><?php echo $post['score']; ?> points</span>
                                        <span><?php echo date('M j', strtotime($post['created_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No recent posts found</p>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-user-plus"></i> New Users</h3>
                        <?php if (!empty($recent_users)): ?>
                            <?php foreach($recent_users as $user): ?>
                                <div class="activity-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        <small>EXP: <?php echo $user['exp']; ?></small>
                                    </div>
                                    <div>
                                        <span><?php echo date('M j', strtotime($user['created_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No new users found</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php endif; ?>
</body>
</html>