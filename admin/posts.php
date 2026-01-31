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
    $post_id = $_POST['post_id'] ?? 0;
    
    switch($action) {
        case 'delete':
            $stmt = $pdo->prepare("UPDATE posts SET status = 'deleted' WHERE id = ?");
            $stmt->execute([$post_id]);
            $message = "Post deleted successfully";
            break;
            
        case 'restore':
            $stmt = $pdo->prepare("UPDATE posts SET status = 'active' WHERE id = ?");
            $stmt->execute([$post_id]);
            $message = "Post restored successfully";
            break;
            
        case 'feature':
            $stmt = $pdo->prepare("UPDATE posts SET featured = 1 WHERE id = ?");
            $stmt->execute([$post_id]);
            $message = "Post featured successfully";
            break;
            
        case 'unfeature':
            $stmt = $pdo->prepare("UPDATE posts SET featured = 0 WHERE id = ?");
            $stmt->execute([$post_id]);
            $message = "Post unfeatured successfully";
            break;
            
        case 'bulk_action':
            $post_ids = $_POST['post_ids'] ?? [];
            $bulk_action = $_POST['bulk_action'] ?? '';
            
            if (!empty($post_ids)) {
                $placeholders = str_repeat('?,', count($post_ids) - 1) . '?';
                
                switch($bulk_action) {
                    case 'delete':
                        $stmt = $pdo->prepare("UPDATE posts SET status = 'deleted' WHERE id IN ($placeholders)");
                        $stmt->execute($post_ids);
                        $message = count($post_ids) . " posts deleted successfully";
                        break;
                        
                    case 'restore':
                        $stmt = $pdo->prepare("UPDATE posts SET status = 'active' WHERE id IN ($placeholders)");
                        $stmt->execute($post_ids);
                        $message = count($post_ids) . " posts restored successfully";
                        break;
                }
            }
            break;
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 'all';
$status = $_GET['status'] ?? 'all';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "p.title LIKE ? OR p.content LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category !== 'all') {
    $where_conditions[] = "p.category = ?";
    $params[] = $category;
}

switch($status) {
    case 'active':
        $where_conditions[] = "p.status = 'active'";
        break;
    case 'deleted':
        $where_conditions[] = "p.status = 'deleted'";
        break;
    case 'featured':
        $where_conditions[] = "p.featured = 1";
        break;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) FROM posts p $where_clause";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_posts = $count_stmt->fetchColumn();
$total_pages = ceil($total_posts / $per_page);

// Get posts with user info
$offset = ($page - 1) * $per_page;
$query = "SELECT p.*, u.username, u.exp,
          (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) as comment_count
          FROM posts p 
          JOIN users u ON p.user_id = u.id 
          $where_clause 
          ORDER BY p.$sort $order 
          LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($query);
$stmt->execute(array_merge($params, [$per_page, $offset]));
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Management - <?php echo SITE_NAME; ?> Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .content-filters {
            background: var(--admin-card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem;
            border: 1px solid var(--admin-border);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-item label {
            font-weight: 500;
            color: var(--admin-text-secondary);
            font-size: 0.9rem;
        }
        
        .filter-item input, .filter-item select {
            padding: 0.75rem;
            background: var(--admin-darker);
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            color: var(--admin-text-primary);
        }
        
        .filter-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .posts-table {
            background: var(--admin-card-bg);
            border-radius: 12px;
            margin: 1.5rem;
            border: 1px solid var(--admin-border);
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--admin-border);
            background: var(--admin-darker);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .bulk-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .bulk-select {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table th {
            background: var(--admin-darker);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--admin-text-primary);
            border-bottom: 1px solid var(--admin-border);
        }
        
        .admin-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--admin-border);
            color: var(--admin-text-primary);
        }
        
        .admin-table tr:hover {
            background: var(--admin-darker);
        }
        
        .post-title {
            font-weight: 500;
            color: var(--admin-text-primary);
            text-decoration: none;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .post-title:hover {
            color: var(--admin-primary);
        }
        
        .post-excerpt {
            color: var(--admin-text-secondary);
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .user-info-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .user-avatar-tiny {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--admin-border);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-badge.active {
            background: rgba(16, 185, 129, 0.15);
            color: var(--admin-success);
        }
        
        .status-badge.deleted {
            background: rgba(239, 68, 68, 0.15);
            color: var(--admin-danger);
        }
        
        .status-badge.featured {
            background: rgba(59, 130, 246, 0.15);
            color: var(--admin-primary);
        }
        
        .action-buttons-cell {
            display: flex;
            gap: 0.5rem;
        }
        
        .pagination-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: var(--admin-darker);
            border-top: 1px solid var(--admin-border);
        }
        
        .pagination-info {
            color: var(--admin-text-secondary);
            font-size: 0.9rem;
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
            background: var(--admin-card-bg);
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
                <a href="posts.php" class="nav-item active">
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
                    <h1><i class="fas fa-comments"></i> Content Management</h1>
                    <div class="admin-info">
                        <span>Managing <?php echo number_format($total_posts); ?> posts</span>
                        <a href="../logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </header>

            <!-- Filters -->
            <section class="content-filters">
                <form method="GET" class="filters-form">
                    <div class="filters-grid">
                        <div class="filter-item">
                            <label>Search Posts</label>
                            <input type="text" name="search" placeholder="Search titles or content..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="filter-item">
                            <label>Category</label>
                            <select name="category">
                                <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat['name']; ?>" <?php echo $category === $cat['name'] ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label>Status</label>
                            <select name="status">
                                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="deleted" <?php echo $status === 'deleted' ? 'selected' : ''; ?>>Deleted</option>
                                <option value="featured" <?php echo $status === 'featured' ? 'selected' : ''; ?>>Featured</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label>Sort By</label>
                            <select name="sort">
                                <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Date Created</option>
                                <option value="score" <?php echo $sort === 'score' ? 'selected' : ''; ?>>Score</option>
                                <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Title</option>
                                <option value="views" <?php echo $sort === 'views' ? 'selected' : ''; ?>>Views</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label>Order</label>
                            <select name="order">
                                <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                                <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="posts.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Reset Filters
                        </a>
                    </div>
                </form>
            </section>

            <!-- Posts Table -->
            <section class="posts-table">
                <?php if (isset($message)): ?>
                    <div class="notification success show">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="table-header">
                    <h3><i class="fas fa-list"></i> Posts List</h3>
                    <div class="bulk-actions">
                        <div class="bulk-select">
                            <input type="checkbox" id="select-all-posts">
                            <span id="selected-count">0</span> selected
                        </div>
                        <select id="bulk-action-select">
                            <option value="">Bulk Action...</option>
                            <option value="delete">Delete Selected</option>
                            <option value="restore">Restore Selected</option>
                        </select>
                        <button id="apply-bulk-action" class="btn btn-danger" disabled>Apply</button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="select-all-header">
                                </th>
                                <th>Post</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Stats</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($posts)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
                                        No posts found matching your criteria
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($posts as $post): ?>
                                    <tr data-post-id="<?php echo $post['id']; ?>">
                                        <td>
                                            <input type="checkbox" class="post-checkbox" value="<?php echo $post['id']; ?>">
                                        </td>
                                        <td>
                                            <a href="../post.php?id=<?php echo $post['id']; ?>" class="post-title" target="_blank">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                            <div class="post-excerpt">
                                                <?php echo htmlspecialchars(substr($post['content'], 0, 100)); ?>...
                                            </div>
                                            <?php if ($post['featured']): ?>
                                                <span class="status-badge featured">FEATURED</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="user-info-cell">
                                                <img src="<?php echo get_user_data($post['user_id'])['avatar'] ?: '../assets/images/default-avatar.png'; ?>" 
                                                     alt="Avatar" class="user-avatar-tiny">
                                                <div>
                                                    <div><?php echo htmlspecialchars($post['username']); ?></div>
                                                    <small><?php echo format_number($post['exp']); ?> EXP</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="background: <?php echo array_column($categories, 'color', 'name')[$post['category']] ?? '#3b82f6'; ?>; 
                                                  color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem;">
                                                <?php echo ucfirst($post['category']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                                <span><i class="fas fa-arrow-up"></i> <?php echo $post['score']; ?> EXP</span>
                                                <span><i class="fas fa-eye"></i> <?php echo $post['views']; ?> views</span>
                                                <span><i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?> comments</span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($post['status'] === 'deleted'): ?>
                                                <span class="status-badge deleted">Deleted</span>
                                            <?php else: ?>
                                                <span class="status-badge active">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                                            <br>
                                            <small><?php echo time_elapsed_string($post['created_at']); ?></small>
                                        </td>
                                        <td>
                                            <div class="action-buttons-cell">
                                                <a href="../post.php?id=<?php echo $post['id']; ?>" target="_blank" 
                                                   class="action-btn view" title="View Post">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($post['status'] === 'active'): ?>
                                                    <?php if ($post['featured']): ?>
                                                        <button class="action-btn unfeature" onclick="unfeaturePost(<?php echo $post['id']; ?>)" title="Unfeature Post">
                                                            <i class="fas fa-star-half-alt"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="action-btn feature" onclick="featurePost(<?php echo $post['id']; ?>)" title="Feature Post">
                                                            <i class="fas fa-star"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <button class="action-btn delete" onclick="deletePost(<?php echo $post['id']; ?>)" title="Delete Post">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="action-btn restore" onclick="restorePost(<?php echo $post['id']; ?>)" title="Restore Post">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-controls">
                        <div class="pagination-info">
                            Showing <?php echo ($page - 1) * $per_page + 1; ?> to <?php echo min($page * $per_page, $total_posts); ?> of <?php echo $total_posts; ?> posts
                        </div>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&status=<?php echo $status; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&status=<?php echo $status; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" 
                                   class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&status=<?php echo $status; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="page-link">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script>
        // Content management JavaScript
        let selectedPosts = new Set();

        // Checkbox handling
        document.getElementById('select-all-posts').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.post-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                if (this.checked) {
                    selectedPosts.add(parseInt(checkbox.value));
                } else {
                    selectedPosts.delete(parseInt(checkbox.value));
                }
            });
            updateSelectedCount();
        });

        document.getElementById('select-all-header').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.post-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                if (this.checked) {
                    selectedPosts.add(parseInt(checkbox.value));
                } else {
                    selectedPosts.delete(parseInt(checkbox.value));
                }
            });
            updateSelectedCount();
        });

        document.querySelectorAll('.post-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const postId = parseInt(this.value);
                if (this.checked) {
                    selectedPosts.add(postId);
                } else {
                    selectedPosts.delete(postId);
                }
                updateSelectedCount();
                
                // Update select all checkboxes
                const allCheckboxes = document.querySelectorAll('.post-checkbox');
                const checkedBoxes = document.querySelectorAll('.post-checkbox:checked');
                const allChecked = allCheckboxes.length === checkedBoxes.length;
                document.getElementById('select-all-posts').checked = allChecked;
                document.getElementById('select-all-header').checked = allChecked;
            });
        });

        function updateSelectedCount() {
            document.getElementById('selected-count').textContent = selectedPosts.size;
            document.getElementById('apply-bulk-action').disabled = selectedPosts.size === 0;
        }

        // Bulk actions
        document.getElementById('apply-bulk-action').addEventListener('click', function() {
            const action = document.getElementById('bulk-action-select').value;
            if (!action || selectedPosts.size === 0) return;
            
            if (confirm(`Are you sure you want to ${action} ${selectedPosts.size} posts?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="bulk_action">
                    <input type="hidden" name="bulk_action" value="${action}">
                `;
                
                selectedPosts.forEach(postId => {
                    form.innerHTML += `<input type="hidden" name="post_ids[]" value="${postId}">`;
                });
                
                document.body.appendChild(form);
                form.submit();
            }
        });

        // Action functions
        function featurePost(postId) {
            if (confirm('Feature this post?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="feature">
                    <input type="hidden" name="post_id" value="${postId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function unfeaturePost(postId) {
            if (confirm('Remove featured status from this post?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="unfeature">
                    <input type="hidden" name="post_id" value="${postId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deletePost(postId) {
            if (confirm('Delete this post? It will be marked as deleted but can be restored.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="post_id" value="${postId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function restorePost(postId) {
            if (confirm('Restore this post?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="restore">
                    <input type="hidden" name="post_id" value="${postId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Auto-hide notifications
        setTimeout(() => {
            document.querySelectorAll('.notification').forEach(notification => {
                notification.classList.remove('show');
            });
        }, 5000);
    </script>
</body>
</html>