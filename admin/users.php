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
    $user_id = $_POST['user_id'] ?? 0;
    
    switch($action) {
        case 'ban':
            $stmt = $pdo->prepare("UPDATE users SET banned = 1, banned_reason = ? WHERE id = ?");
            $stmt->execute([$_POST['reason'] ?? 'Administrative action', $user_id]);
            $message = "User banned successfully";
            break;
            
        case 'unban':
            $stmt = $pdo->prepare("UPDATE users SET banned = 0, banned_reason = NULL WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = "User unbanned successfully";
            break;
            
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = "User deleted successfully";
            break;
            
        case 'reset_exp':
            $stmt = $pdo->prepare("UPDATE users SET exp = 0 WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = "User EXP reset successfully";
            break;
            
        case 'bulk_action':
            $user_ids = $_POST['user_ids'] ?? [];
            $bulk_action = $_POST['bulk_action'] ?? '';
            
            if (!empty($user_ids)) {
                $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
                
                switch($bulk_action) {
                    case 'ban':
                        $stmt = $pdo->prepare("UPDATE users SET banned = 1 WHERE id IN ($placeholders)");
                        $stmt->execute($user_ids);
                        $message = count($user_ids) . " users banned successfully";
                        break;
                        
                    case 'delete':
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
                        $stmt->execute($user_ids);
                        $message = count($user_ids) . " users deleted successfully";
                        break;
                }
            }
            break;
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

switch($status) {
    case 'active':
        $where_conditions[] = "banned = 0";
        break;
    case 'banned':
        $where_conditions[] = "banned = 1";
        break;
    case 'verified':
        $where_conditions[] = "verified = 1";
        break;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) FROM users $where_clause";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $per_page);

// Get users
$offset = ($page - 1) * $per_page;
$query = "SELECT * FROM users $where_clause ORDER BY $sort $order LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($query);
$stmt->execute(array_merge($params, [$per_page, $offset]));
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user_levels = [
    0 => 'Newbie',
    100 => 'Beginner',
    500 => 'Intermediate',
    1000 => 'Advanced',
    2500 => 'Expert',
    5000 => 'Master',
    10000 => 'Legend'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - <?php echo SITE_NAME; ?> Admin</title>
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
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="users.php" class="nav-item active">
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
                    <h1><i class="fas fa-users"></i> User Management</h1>
                    <div class="admin-info">
                        <span>Managing <?php echo number_format($total_users); ?> users</span>
                        <a href="../logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </header>

            <!-- Filters and Search -->
            <section class="filters-section">
                <div class="filters-container">
                    <form method="GET" class="filters-form">
                        <div class="filter-group">
                            <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </div>
                        
                        <div class="filter-group">
                            <select name="status">
                                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="banned" <?php echo $status === 'banned' ? 'selected' : ''; ?>>Banned</option>
                                <option value="verified" <?php echo $status === 'verified' ? 'selected' : ''; ?>>Verified</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <select name="sort">
                                <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Join Date</option>
                                <option value="username" <?php echo $sort === 'username' ? 'selected' : ''; ?>>Username</option>
                                <option value="exp" <?php echo $sort === 'exp' ? 'selected' : ''; ?>>EXP</option>
                                <option value="last_login" <?php echo $sort === 'last_login' ? 'selected' : ''; ?>>Last Login</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <select name="order">
                                <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                                <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="users.php" class="btn btn-secondary">Reset</a>
                    </form>
                </div>
            </section>

            <!-- Bulk Actions -->
            <section class="bulk-actions-section">
                <div class="bulk-actions-container">
                    <div class="selected-count">
                        <span id="selected-count">0</span> users selected
                    </div>
                    <div class="bulk-action-buttons">
                        <select id="bulk-action-select">
                            <option value="">Select action...</option>
                            <option value="ban">Ban Selected</option>
                            <option value="delete">Delete Selected</option>
                        </select>
                        <button id="apply-bulk-action" class="btn btn-danger" disabled>Apply</button>
                    </div>
                </div>
            </section>

            <!-- Users Table -->
            <section class="users-table-section">
                <?php if (isset($message)): ?>
                    <div class="notification success show">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="select-all">
                                </th>
                                <th>User</th>
                                <th>EXP Level</th>
                                <th>Status</th>
                                <th>Posts</th>
                                <th>Comments</th>
                                <th>Join Date</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="9" class="no-data">No users found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($users as $user): 
                                    // Get user stats
                                    $post_count = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
                                    $post_count->execute([$user['id']]);
                                    $posts = $post_count->fetchColumn();
                                    
                                    $comment_count = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
                                    $comment_count->execute([$user['id']]);
                                    $comments = $comment_count->fetchColumn();
                                    
                                    // Determine user level
                                    $user_level = 'Newbie';
                                    foreach($user_levels as $threshold => $level) {
                                        if ($user['exp'] >= $threshold) {
                                            $user_level = $level;
                                        }
                                    }
                                ?>
                                    <tr data-user-id="<?php echo $user['id']; ?>">
                                        <td>
                                            <input type="checkbox" class="user-checkbox" value="<?php echo $user['id']; ?>">
                                        </td>
                                        <td>
                                            <div class="user-info">
                                                <img src="<?php echo $user['avatar'] ?: '../assets/images/default-avatar.png'; ?>" 
                                                     alt="Avatar" class="user-avatar-small">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                    <br>
                                                    <small><?php echo htmlspecialchars($user['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="exp-display">
                                                <span class="exp-value"><?php echo format_number($user['exp']); ?> EXP</span>
                                                <span class="user-level-badge"><?php echo $user_level; ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($user['banned']): ?>
                                                <span class="status-badge banned">Banned</span>
                                                <?php if ($user['banned_reason']): ?>
                                                    <small class="banned-reason"><?php echo htmlspecialchars($user['banned_reason']); ?></small>
                                                <?php endif; ?>
                                            <?php elseif (!$user['verified']): ?>
                                                <span class="status-badge pending">Unverified</span>
                                            <?php else: ?>
                                                <span class="status-badge active">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $posts; ?></td>
                                        <td><?php echo $comments; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                        <td><?php echo $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never'; ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn view" onclick="viewUser(<?php echo $user['id']; ?>)" title="View Profile">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($user['banned']): ?>
                                                    <button class="action-btn unban" onclick="unbanUser(<?php echo $user['id']; ?>)" title="Unban User">
                                                        <i class="fas fa-user-check"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="action-btn ban" onclick="banUser(<?php echo $user['id']; ?>)" title="Ban User">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="action-btn reset" onclick="resetExp(<?php echo $user['id']; ?>)" title="Reset EXP">
                                                    <i class="fas fa-sync"></i>
                                                </button>
                                                <button class="action-btn delete" onclick="deleteUser(<?php echo $user['id']; ?>)" title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
                    <div class="pagination-container">
                        <div class="pagination-info">
                            Showing <?php echo ($page - 1) * $per_page + 1; ?> to <?php echo min($page * $per_page, $total_users); ?> of <?php echo $total_users; ?> users
                        </div>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" 
                                   class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="page-link">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Ban User Modal -->
    <div id="ban-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ban User</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="ban">
                <input type="hidden" name="user_id" id="ban-user-id">
                <div class="form-group">
                    <label class="form-label">Reason for banning:</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Enter reason for banning this user..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary modal-close-btn">Cancel</button>
                    <button type="submit" class="btn btn-danger">Ban User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // User management JavaScript
        let selectedUsers = new Set();

        // Select all checkbox
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                if (this.checked) {
                    selectedUsers.add(parseInt(checkbox.value));
                } else {
                    selectedUsers.delete(parseInt(checkbox.value));
                }
            });
            updateSelectedCount();
        });

        // Individual checkboxes
        document.querySelectorAll('.user-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const userId = parseInt(this.value);
                if (this.checked) {
                    selectedUsers.add(userId);
                } else {
                    selectedUsers.delete(userId);
                }
                updateSelectedCount();
                
                // Update select all checkbox
                const allCheckboxes = document.querySelectorAll('.user-checkbox');
                const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
                document.getElementById('select-all').checked = allCheckboxes.length === checkedBoxes.length;
            });
        });

        function updateSelectedCount() {
            document.getElementById('selected-count').textContent = selectedUsers.size;
            document.getElementById('apply-bulk-action').disabled = selectedUsers.size === 0;
        }

        // Bulk actions
        document.getElementById('apply-bulk-action').addEventListener('click', function() {
            const action = document.getElementById('bulk-action-select').value;
            if (!action || selectedUsers.size === 0) return;
            
            if (confirm(`Are you sure you want to ${action} ${selectedUsers.size} users?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="bulk_action">
                    <input type="hidden" name="bulk_action" value="${action}">
                `;
                
                selectedUsers.forEach(userId => {
                    form.innerHTML += `<input type="hidden" name="user_ids[]" value="${userId}">`;
                });
                
                document.body.appendChild(form);
                form.submit();
            }
        });

        // Action functions
        function viewUser(userId) {
            window.open(`../profile.php?id=${userId}`, '_blank');
        }

        function banUser(userId) {
            document.getElementById('ban-user-id').value = userId;
            document.getElementById('ban-modal').classList.add('show');
        }

        function unbanUser(userId) {
            if (confirm('Are you sure you want to unban this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="unban">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function resetExp(userId) {
            if (confirm('Are you sure you want to reset this user\'s EXP to 0?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="reset_exp">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to permanently delete this user? This cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Modal handling
        document.querySelectorAll('.modal-close, .modal-close-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.classList.remove('show');
                });
            });
        });

        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('show');
                }
            });
        });

        // Auto-hide notifications
        setTimeout(() => {
            document.querySelectorAll('.notification').forEach(notification => {
                notification.classList.remove('show');
            });
        }, 5000);
    </script>
</body>
</html>