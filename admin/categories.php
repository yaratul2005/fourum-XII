<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Check admin authentication
if (!is_admin()) {
    header('Location: ../login.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$category_id = intval($_GET['id'] ?? 0);

$message = '';
$message_type = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_category']) && $category_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET status = 'active' WHERE id = ?");
            if ($stmt->execute([$category_id])) {
                // Get category info for notification
                $stmt = $pdo->prepare("SELECT name, created_by FROM categories WHERE id = ?");
                $stmt->execute([$category_id]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($category && $category['created_by']) {
                    // Award EXP to category creator
                    award_exp($category['created_by'], 50, "Category approved: {$category['name']}");
                    
                    // Create notification
                    create_notification($category['created_by'], 'category_approved', 
                        "Your category '{$category['name']}' has been approved!", 
                        "../categories.php");
                }
                
                $message = "Category approved successfully!";
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error approving category: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['reject_category']) && $category_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET status = 'rejected' WHERE id = ?");
            if ($stmt->execute([$category_id])) {
                $message = "Category rejected";
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error rejecting category: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['delete_category']) && $category_id > 0) {
        try {
            // Don't allow deletion of default Unknown category
            if ($category_id === 0) {
                $message = "Cannot delete the default Unknown category";
                $message_type = 'error';
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                if ($stmt->execute([$category_id])) {
                    $message = "Category deleted successfully";
                    $message_type = 'success';
                }
            }
        } catch (Exception $e) {
            $message = 'Error deleting category: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$sort_by = $_GET['sort'] ?? 'created_at';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get categories
$query = "SELECT c.*, u.username as creator_name 
          FROM categories c 
          LEFT JOIN users u ON c.created_by = u.id 
          $where_clause 
          ORDER BY " . ($sort_by === 'name' ? 'c.name ASC' : 'c.created_at DESC');

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get specific category if viewing/editing
$category = null;
if ($category_id > 0 && $action === 'view') {
    $stmt = $pdo->prepare("SELECT c.*, u.username as creator_name FROM categories c LEFT JOIN users u ON c.created_by = u.id WHERE c.id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Calculate statistics
$stats = [
    'active' => 0,
    'pending' => 0,
    'rejected' => 0,
    'total' => count($categories)
];

foreach ($categories as $cat) {
    $stats[$cat['status']] = ($stats[$cat['status']] ?? 0) + 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--admin-card-bg);
            border: 1px solid var(--admin-border);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-active { color: var(--admin-success); }
        .stat-pending { color: var(--admin-warning); }
        .stat-rejected { color: var(--admin-danger); }
        .stat-total { color: var(--admin-primary); }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .category-card {
            background: var(--admin-card-bg);
            border: 1px solid var(--admin-border);
            border-radius: 10px;
            padding: 1.5rem;
        }
        
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--admin-border);
        }
        
        .category-name {
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid #10b981;
        }
        
        .status-pending {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            border: 1px solid #f59e0b;
        }
        
        .status-rejected {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid #ef4444;
        }
        
        .category-details {
            margin-bottom: 1rem;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .detail-label {
            color: var(--admin-text-secondary);
        }
        
        .detail-value {
            color: var(--admin-text-primary);
            font-weight: 500;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .btn-warning {
            background: var(--admin-warning);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-danger {
            background: var(--admin-danger);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: var(--admin-bg);
            border-radius: 10px;
            padding: 2rem;
            width: 90%;
            max-width: 600px;
            border: 1px solid var(--admin-border);
        }
        
        @media (max-width: 768px) {
            .category-grid {
                grid-template-columns: 1fr;
            }
            
            .category-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .action-buttons {
                width: 100%;
                justify-content: stretch;
            }
            
            .action-buttons .btn {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1><i class="fas fa-tags"></i> Category Management</h1>
                <div class="header-actions">
                    <a href="?status=pending" class="btn btn-warning">
                        <i class="fas fa-clock"></i> Pending (<?php echo $stats['pending']; ?>)
                    </a>
                    <a href="?status=active" class="btn btn-success">
                        <i class="fas fa-check"></i> Active (<?php echo $stats['active']; ?>)
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number stat-active"><?php echo $stats['active']; ?></div>
                    <div>Active Categories</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number stat-pending"><?php echo $stats['pending']; ?></div>
                    <div>Pending Approval</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number stat-rejected"><?php echo $stats['rejected']; ?></div>
                    <div>Rejected</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number stat-total"><?php echo $stats['total']; ?></div>
                    <div>Total Categories</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="admin-content">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-filter"></i> Filter Categories</h2>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="filter-form">
                            <input type="hidden" name="page" value="categories">
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="sort">Sort By</label>
                                <select name="sort" id="sort" class="form-control">
                                    <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Newest First</option>
                                    <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Apply Filters
                                </button>
                                <a href="?page=categories" class="btn btn-outline">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Categories List -->
            <div class="admin-content">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-list"></i> Categories</h2>
                        <span><?php echo count($categories); ?> categories found</span>
                    </div>
                    
                    <div class="card-body">
                        <?php if (empty($categories)): ?>
                            <div class="empty-state">
                                <i class="fas fa-tags"></i>
                                <p>No categories found matching your criteria</p>
                                <?php if ($status_filter !== 'all'): ?>
                                    <a href="?page=categories" class="btn btn-primary">View All Categories</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="category-grid">
                                <?php foreach ($categories as $cat): ?>
                                    <div class="category-card">
                                        <div class="category-header">
                                            <div class="category-name">
                                                <i class="fas fa-<?php echo htmlspecialchars($cat['icon']); ?>" 
                                                   style="color: <?php echo htmlspecialchars($cat['color']); ?>;"></i>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                                <?php if ($cat['id'] === 0): ?>
                                                    <span style="font-size: 0.8rem; background: #6c757d; padding: 0.2rem 0.5rem; border-radius: 10px;">DEFAULT</span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="status-badge status-<?php echo $cat['status']; ?>">
                                                <?php echo ucfirst($cat['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="category-details">
                                            <?php if ($cat['description']): ?>
                                                <div class="detail-item">
                                                    <span class="detail-label">Description:</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars(substr($cat['description'], 0, 100)); ?><?php echo strlen($cat['description']) > 100 ? '...' : ''; ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="detail-item">
                                                <span class="detail-label">Creator:</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($cat['creator_name'] ?? 'System'); ?></span>
                                            </div>
                                            
                                            <div class="detail-item">
                                                <span class="detail-label">Posts:</span>
                                                <span class="detail-value"><?php echo $cat['post_count']; ?></span>
                                            </div>
                                            
                                            <div class="detail-item">
                                                <span class="detail-label">Created:</span>
                                                <span class="detail-value"><?php echo date('M j, Y', strtotime($cat['created_at'])); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="action-buttons">
                                            <a href="?page=categories&action=view&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            
                                            <?php if ($cat['status'] === 'pending' && $cat['id'] > 0): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                                    <button type="submit" name="approve_category" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                                    <button type="submit" name="reject_category" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($cat['id'] > 0): ?>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
            <p>Are you sure you want to delete the category "<span id="deleteCategoryName"></span>"?</p>
            <p>This action cannot be undone and will affect all posts in this category.</p>
            
            <form method="POST" id="deleteForm" style="display: flex; gap: 1rem; margin-top: 1rem;">
                <input type="hidden" name="id" id="deleteCategoryId">
                <button type="submit" name="delete_category" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Yes, Delete
                </button>
                <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </form>
        </div>
    </div>

    <script>
        function confirmDelete(categoryId, categoryName) {
            document.getElementById('deleteCategoryId').value = categoryId;
            document.getElementById('deleteCategoryName').textContent = categoryName;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('deleteModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>