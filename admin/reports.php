<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Check admin access
if (!is_logged_in() || get_user_data(get_current_user_id())['username'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $report_id = $_POST['report_id'] ?? 0;
    
    switch($action) {
        case 'resolve':
            $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved', resolved_at = NOW(), resolved_by = ? WHERE id = ?");
            $stmt->execute([get_current_user_id(), $report_id]);
            
            // Take action on reported content
            $report_stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
            $report_stmt->execute([$report_id]);
            $report = $report_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($report) {
                if ($report['content_type'] === 'post') {
                    $content_stmt = $pdo->prepare("UPDATE posts SET status = 'deleted' WHERE id = ?");
                    $content_stmt->execute([$report['content_id']]);
                } elseif ($report['content_type'] === 'comment') {
                    $content_stmt = $pdo->prepare("UPDATE comments SET status = 'deleted' WHERE id = ?");
                    $content_stmt->execute([$report['content_id']]);
                }
            }
            
            $message = "Report resolved and content removed";
            break;
            
        case 'dismiss':
            $stmt = $pdo->prepare("UPDATE reports SET status = 'dismissed', resolved_at = NOW(), resolved_by = ? WHERE id = ?");
            $stmt->execute([get_current_user_id(), $report_id]);
            $message = "Report dismissed";
            break;
            
        case 'ban_user':
            $report_stmt = $pdo->prepare("SELECT user_id FROM reports WHERE id = ?");
            $report_stmt->execute([$report_id]);
            $report = $report_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($report) {
                $ban_stmt = $pdo->prepare("UPDATE users SET banned = 1, banned_reason = 'Violated community guidelines' WHERE id = ?");
                $ban_stmt->execute([$report['user_id']]);
                
                $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved', resolved_at = NOW(), resolved_by = ? WHERE id = ?");
                $stmt->execute([get_current_user_id(), $report_id]);
                
                $message = "User banned and report resolved";
            }
            break;
    }
}

// Get filter parameters
$status = $_GET['status'] ?? 'pending';
$type = $_GET['type'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;

// Build query
$where_conditions = ["r.status = ?"];
$params = [$status];

if ($type !== 'all') {
    $where_conditions[] = "r.content_type = ?";
    $params[] = $type;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) FROM reports r $where_clause";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_reports = $count_stmt->fetchColumn();
$total_pages = ceil($total_reports / $per_page);

// Get reports with related data
$offset = ($page - 1) * $per_page;
$query = "SELECT r.*, 
                 u.username as reporter_username,
                 u.email as reporter_email,
                 target_user.username as target_username,
                 CASE 
                     WHEN r.content_type = 'post' THEN p.title
                     WHEN r.content_type = 'comment' THEN LEFT(c.content, 100)
                     ELSE 'Content not found'
                 END as content_preview,
                 CASE 
                     WHEN r.content_type = 'post' THEN p.user_id
                     WHEN r.content_type = 'comment' THEN c.user_id
                     ELSE NULL
                 END as target_user_id
          FROM reports r
          JOIN users u ON r.user_id = u.id
          JOIN users target_user ON r.target_user_id = target_user.id
          LEFT JOIN posts p ON r.content_type = 'post' AND r.content_id = p.id
          LEFT JOIN comments c ON r.content_type = 'comment' AND r.content_id = c.id
          $where_clause
          ORDER BY r.created_at DESC
          LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($query);
$stmt->execute(array_merge($params, [$per_page, $offset]));
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports Management - <?php echo SITE_NAME; ?> Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .reports-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1.5rem;
        }
        
        .reports-stats {
            display: flex;
            gap: 1rem;
        }
        
        .stat-badge {
            background: var(--admin-card-bg);
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            border: 1px solid var(--admin-border);
        }
        
        .stat-badge.pending {
            border-left: 3px solid var(--admin-warning);
        }
        
        .stat-badge.resolved {
            border-left: 3px solid var(--admin-success);
        }
        
        .stat-badge.dismissed {
            border-left: 3px solid var(--admin-text-secondary);
        }
        
        .reports-filters {
            background: var(--admin-card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem;
            border: 1px solid var(--admin-border);
        }
        
        .filter-row {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--admin-text-secondary);
        }
        
        .filter-group select {
            width: 100%;
            padding: 0.75rem;
            background: var(--admin-darker);
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            color: var(--admin-text-primary);
        }
        
        .reports-list {
            margin: 1.5rem;
        }
        
        .report-card {
            background: var(--admin-card-bg);
            border-radius: 12px;
            border: 1px solid var(--admin-border);
            margin-bottom: 1rem;
            overflow: hidden;
            transition: var(--admin-transition);
        }
        
        .report-card:hover {
            border-color: var(--admin-primary);
            transform: translateY(-2px);
        }
        
        .report-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--admin-border);
            background: var(--admin-darker);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .report-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--admin-text-primary);
        }
        
        .report-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .report-status.pending {
            background: rgba(245, 158, 11, 0.15);
            color: var(--admin-warning);
        }
        
        .report-status.resolved {
            background: rgba(16, 185, 129, 0.15);
            color: var(--admin-success);
        }
        
        .report-status.dismissed {
            background: rgba(100, 116, 139, 0.15);
            color: var(--admin-text-secondary);
        }
        
        .report-content {
            padding: 1.5rem;
        }
        
        .report-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .detail-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .detail-label {
            font-size: 0.9rem;
            color: var(--admin-text-secondary);
            font-weight: 500;
        }
        
        .detail-value {
            color: var(--admin-text-primary);
            font-weight: 500;
        }
        
        .report-preview {
            background: var(--admin-darker);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            border-left: 3px solid var(--admin-primary);
        }
        
        .preview-content {
            color: var(--admin-text-primary);
            line-height: 1.6;
        }
        
        .preview-meta {
            display: flex;
            gap: 1rem;
            margin-top: 0.75rem;
            font-size: 0.9rem;
            color: var(--admin-text-secondary);
        }
        
        .report-actions {
            display: flex;
            gap: 0.75rem;
            padding-top: 1rem;
            border-top: 1px solid var(--admin-border);
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: var(--admin-transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .action-btn.resolve {
            background: var(--admin-success);
            color: white;
        }
        
        .action-btn.resolve:hover {
            background: #059669;
        }
        
        .action-btn.dismiss {
            background: var(--admin-text-secondary);
            color: var(--admin-dark);
        }
        
        .action-btn.dismiss:hover {
            background: var(--admin-text-primary);
        }
        
        .action-btn.ban {
            background: var(--admin-danger);
            color: white;
        }
        
        .action-btn.ban:hover {
            background: #dc2626;
        }
        
        .action-btn.view {
            background: var(--admin-primary);
            color: white;
            text-decoration: none;
        }
        
        .action-btn.view:hover {
            background: var(--admin-primary-hover);
        }
        
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: var(--admin-card-bg);
            border-radius: 12px;
            margin: 1.5rem;
            border: 1px solid var(--admin-border);
        }
        
        .pagination-info {
            color: var(--admin-text-secondary);
        }
        
        .pagination {
            display: flex;
            gap: 0.5rem;
        }
        
        .page-link {
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            text-decoration: none;
            color: var(--admin-text-primary);
            background: var(--admin-darker);
            border: 1px solid var(--admin-border);
            transition: var(--admin-transition);
        }
        
        .page-link:hover {
            background: var(--admin-primary);
            color: white;
            border-color: var(--admin-primary);
        }
        
        .page-link.active {
            background: var(--admin-primary);
            color: white;
            border-color: var(--admin-primary);
        }
        
        .no-reports {
            text-align: center;
            padding: 3rem;
            color: var(--admin-text-muted);
        }
        
        .no-reports i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
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
                <a href="dashboard.php" class="nav-item">
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
                <a href="reports.php" class="nav-item active">
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
                    <h1><i class="fas fa-flag"></i> Reports Management</h1>
                    <div class="admin-info">
                        <span>Handling community reports</span>
                        <a href="../logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </header>

            <!-- Reports Stats -->
            <div class="reports-header">
                <div class="reports-stats">
                    <?php
                    $pending_count = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
                    $resolved_count = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'resolved'")->fetchColumn();
                    $dismissed_count = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'dismissed'")->fetchColumn();
                    ?>
                    <div class="stat-badge pending">
                        <strong><?php echo $pending_count; ?></strong> Pending
                    </div>
                    <div class="stat-badge resolved">
                        <strong><?php echo $resolved_count; ?></strong> Resolved
                    </div>
                    <div class="stat-badge dismissed">
                        <strong><?php echo $dismissed_count; ?></strong> Dismissed
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <section class="reports-filters">
                <form method="GET">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                <option value="dismissed" <?php echo $status === 'dismissed' ? 'selected' : ''; ?>>Dismissed</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Content Type</label>
                            <select name="type">
                                <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All Types</option>
                                <option value="post" <?php echo $type === 'post' ? 'selected' : ''; ?>>Posts</option>
                                <option value="comment" <?php echo $type === 'comment' ? 'selected' : ''; ?>>Comments</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                        </div>
                        
                        <div class="filter-group">
                            <a href="reports.php" class="btn btn-secondary" style="width: 100%; text-align: center;">
                                <i class="fas fa-times"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Reports List -->
            <section class="reports-list">
                <?php if (isset($message)): ?>
                    <div class="notification success show">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($reports)): ?>
                    <div class="no-reports">
                        <i class="fas fa-flag"></i>
                        <h3>No reports found</h3>
                        <p>There are currently no reports matching your criteria.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($reports as $report): ?>
                        <div class="report-card">
                            <div class="report-header">
                                <div class="report-title">
                                    <i class="fas fa-flag"></i>
                                    Report #<?php echo $report['id']; ?> - 
                                    <?php echo ucfirst($report['content_type']); ?> Violation
                                </div>
                                <span class="report-status <?php echo $report['status']; ?>">
                                    <?php echo ucfirst($report['status']); ?>
                                </span>
                            </div>
                            
                            <div class="report-content">
                                <div class="report-details">
                                    <div class="detail-group">
                                        <span class="detail-label">Reported By</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($report['reporter_username']); ?></span>
                                        <small><?php echo htmlspecialchars($report['reporter_email']); ?></small>
                                    </div>
                                    
                                    <div class="detail-group">
                                        <span class="detail-label">Target User</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($report['target_username']); ?></span>
                                    </div>
                                    
                                    <div class="detail-group">
                                        <span class="detail-label">Reason</span>
                                        <span class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $report['reason'])); ?></span>
                                    </div>
                                    
                                    <div class="detail-group">
                                        <span class="detail-label">Reported</span>
                                        <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($report['created_at'])); ?></span>
                                        <small><?php echo time_elapsed_string($report['created_at']); ?></small>
                                    </div>
                                </div>
                                
                                <?php if ($report['content_preview']): ?>
                                    <div class="report-preview">
                                        <div class="detail-label">Reported Content:</div>
                                        <div class="preview-content">
                                            <?php echo htmlspecialchars($report['content_preview']); ?>
                                        </div>
                                        <div class="preview-meta">
                                            <span><i class="fas fa-user"></i> Author: <?php echo htmlspecialchars($report['target_username']); ?></span>
                                            <?php if ($report['content_type'] === 'post'): ?>
                                                <span><i class="fas fa-comments"></i> Post</span>
                                            <?php else: ?>
                                                <span><i class="fas fa-comment"></i> Comment</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($report['description']): ?>
                                    <div class="report-preview">
                                        <div class="detail-label">Additional Details:</div>
                                        <div class="preview-content">
                                            <?php echo htmlspecialchars($report['description']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($report['status'] === 'pending'): ?>
                                    <div class="report-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="resolve">
                                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                            <button type="submit" class="action-btn resolve" title="Resolve Report">
                                                <i class="fas fa-check"></i> Resolve & Remove Content
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="ban_user">
                                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                            <button type="submit" class="action-btn ban" 
                                                    onclick="return confirm('Ban user <?php echo htmlspecialchars($report['target_username']); ?> and resolve report?')"
                                                    title="Ban User">
                                                <i class="fas fa-ban"></i> Ban User
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="dismiss">
                                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                            <button type="submit" class="action-btn dismiss" title="Dismiss Report">
                                                <i class="fas fa-times"></i> Dismiss
                                            </button>
                                        </form>
                                        
                                        <a href="<?php echo $report['content_type'] === 'post' ? '../post.php?id=' . $report['content_id'] : '#'; ?>" 
                                           class="action-btn view" target="_blank" title="View Content">
                                            <i class="fas fa-eye"></i> View <?php echo ucfirst($report['content_type']); ?>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="report-actions">
                                        <?php if ($report['resolved_at'] && $report['resolved_by']): 
                                            $resolver = get_user_data($report['resolved_by']);
                                        ?>
                                            <div class="detail-group">
                                                <span class="detail-label">Resolved By</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($resolver['username']); ?></span>
                                                <small><?php echo date('M j, Y g:i A', strtotime($report['resolved_at'])); ?></small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <a href="<?php echo $report['content_type'] === 'post' ? '../post.php?id=' . $report['content_id'] : '#'; ?>" 
                                           class="action-btn view" target="_blank">
                                            <i class="fas fa-eye"></i> View <?php echo ucfirst($report['content_type']); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Showing <?php echo ($page - 1) * $per_page + 1; ?> to <?php echo min($page * $per_page, $total_reports); ?> of <?php echo $total_reports; ?> reports
                    </div>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>&type=<?php echo $type; ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&type=<?php echo $type; ?>" 
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>&type=<?php echo $type; ?>" class="page-link">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Auto-hide notifications
        setTimeout(() => {
            document.querySelectorAll('.notification').forEach(notification => {
                notification.classList.remove('show');
            });
        }, 5000);
    </script>
</body>
</html>