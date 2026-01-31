<?php
// Categories Page - User Created Categories
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/notifications.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_user = null;
if (is_logged_in()) {
    $current_user = get_user_data(get_current_user_id());
}

$errors = [];
$message = '';
$message_type = '';

// Handle category creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    if (!$current_user) {
        $errors[] = 'You must be logged in to create categories.';
    } else {
        // Check if KYC is required and user is verified
        $kyc_required = get_setting('kyc_required_for_categories', '1');
        if ($kyc_required === '1' && $current_user['kyc_status'] !== 'verified') {
            $errors[] = 'You must be KYC verified to create categories. Submit verification at <a href="kyc-submit.php">KYC Verification</a>.';
        }
        
        // Validate input
        $name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['category_description'] ?? '');
        
        if (empty($name)) {
            $errors[] = 'Category name is required.';
        }
        
        if (strlen($name) > 50) {
            $errors[] = 'Category name must be 50 characters or less.';
        }
        
        if (strlen($description) > 500) {
            $errors[] = 'Description must be 500 characters or less.';
        }
        
        // Check if category name already exists
        $slug = create_slug($name);
        $stmt = $pdo->prepare("SELECT id FROM user_categories WHERE slug = ? OR name = ?");
        $stmt->execute([$slug, $name]);
        if ($stmt->fetch()) {
            $errors[] = 'A category with this name already exists.';
        }
        
        if (empty($errors)) {
            try {
                // Determine initial status (auto-approve high EXP users)
                $min_exp_for_auto = (int)get_setting('kyc_auto_approval_threshold', '1000');
                $status = ($current_user['exp'] >= $min_exp_for_auto) ? 'active' : 'pending';
                
                $stmt = $pdo->prepare("
                    INSERT INTO user_categories 
                    (name, description, slug, created_by, kyc_verified_only, status, created_at)
                    VALUES (?, ?, ?, ?, 0, ?, NOW())
                ");
                
                if ($stmt->execute([$name, $description, $slug, $current_user['id'], $status])) {
                    $category_id = $pdo->lastInsertId();
                    
                    if ($status === 'active') {
                        // Auto-approved
                        $stmt = $pdo->prepare("UPDATE user_categories SET approved_at = NOW(), approved_by = ? WHERE id = ?");
                        $stmt->execute([1, $category_id]); // Assuming admin ID is 1
                        
                        $message = 'Category created successfully!';
                        $message_type = 'success';
                        
                        // Send notification
                        send_user_notification($current_user['id'], 'Category Approved', "Your category '{$name}' has been created and is now active!", 'category_approved', $category_id, 'category');
                    } else {
                        // Pending approval
                        $message = 'Category submitted for approval. It will be reviewed by administrators.';
                        $message_type = 'info';
                        
                        // Send notification to admins
                        send_admin_notification('New Category Pending Approval', "User {$current_user['username']} has submitted a new category: {$name}");
                    }
                    
                    // Clear form data
                    $_POST = [];
                } else {
                    $errors[] = 'Failed to create category. Please try again.';
                }
            } catch (Exception $e) {
                error_log("Category creation error: " . $e->getMessage());
                $errors[] = 'An error occurred while creating the category.';
            }
        }
    }
}

// Get categories with filtering
$status_filter = $_GET['status'] ?? 'active';
$sort_by = $_GET['sort'] ?? 'newest';

$where_clause = "WHERE uc.status = ?";
$params = [$status_filter];

// Apply additional filters
if (isset($_GET['kyc_only']) && $_GET['kyc_only'] === '1') {
    $where_clause .= " AND uc.kyc_verified_only = 1";
}

// Sorting
$order_by = "uc.created_at DESC";
switch ($sort_by) {
    case 'oldest':
        $order_by = "uc.created_at ASC";
        break;
    case 'name':
        $order_by = "uc.name ASC";
        break;
    case 'posts':
        $order_by = "(SELECT COUNT(*) FROM posts p WHERE p.category_id = uc.id) DESC, uc.created_at DESC";
        break;
}

// Get categories
$stmt = $pdo->prepare("
    SELECT uc.*, u.username as creator_name, u.exp as creator_exp,
           (SELECT COUNT(*) FROM posts p WHERE p.category_id = uc.id) as post_count,
           approver.username as approver_name
    FROM user_categories uc
    JOIN users u ON uc.created_by = u.id
    LEFT JOIN users approver ON uc.approved_by = approver.id
    {$where_clause}
    ORDER BY {$order_by}
");
$stmt->execute($params);
$categories = $stmt->fetchAll();

// Get statistics
$stats_stmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        COUNT(*) as total
    FROM user_categories
");
$stats = $stats_stmt->fetch();

// Get user's pending categories
$user_pending_categories = [];
if ($current_user) {
    $stmt = $pdo->prepare("SELECT * FROM user_categories WHERE created_by = ? AND status = 'pending' ORDER BY created_at DESC");
    $stmt->execute([$current_user['id']]);
    $user_pending_categories = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .categories-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border: 1px solid var(--border-color);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-active { color: #10b981; }
        .stat-pending { color: #f59e0b; }
        .stat-rejected { color: #ef4444; }
        .stat-total { color: var(--text-primary); }
        
        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
            padding: 20px;
            background: var(--card-bg);
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-select {
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            color: var(--text-primary);
        }
        
        .create-category-btn {
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
        }
        
        .create-category-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .category-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            border-color: var(--primary);
        }
        
        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .category-header {
            margin-bottom: 15px;
        }
        
        .category-name {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0 0 10px 0;
            color: var(--text-primary);
        }
        
        .category-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .category-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 15px;
        }
        
        .category-stats {
            display: flex;
            gap: 15px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status-pending { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .status-rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        
        .create-category-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .close-modal:hover {
            color: var(--text-primary);
        }
        
        .kyc-requirement {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        
        .pending-categories {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-container">
        <div class="container">
            <div class="categories-header">
                <h1><i class="fas fa-tags"></i> Community Categories</h1>
                <p>Discover and create discussion categories</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo $message; ?>
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
            
            <!-- Filters and Create Button -->
            <div class="filters-bar">
                <div class="filter-group">
                    <label for="status-filter">Status:</label>
                    <select id="status-filter" class="filter-select" onchange="applyFilters()">
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort-filter">Sort by:</label>
                    <select id="sort-filter" class="filter-select" onchange="applyFilters()">
                        <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest</option>
                        <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                        <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name</option>
                        <option value="posts" <?php echo $sort_by === 'posts' ? 'selected' : ''; ?>>Most Posts</option>
                    </select>
                </div>
                
                <?php if ($current_user): ?>
                    <button class="create-category-btn" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Create Category
                    </button>
                <?php else: ?>
                    <a href="register.php" class="create-category-btn">
                        <i class="fas fa-user-plus"></i> Register to Create
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- KYC Requirement Notice -->
            <?php if ($current_user && get_setting('kyc_required_for_categories', '1') === '1' && $current_user['kyc_status'] !== 'verified'): ?>
                <div class="kyc-requirement">
                    <h3><i class="fas fa-id-card"></i> KYC Verification Required</h3>
                    <p>You need to be KYC verified to create categories. This helps maintain community quality.</p>
                    <a href="kyc-submit.php" class="btn btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-id-card"></i> Verify Identity Now
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- User's Pending Categories -->
            <?php if (!empty($user_pending_categories)): ?>
                <div class="pending-categories">
                    <h3><i class="fas fa-clock"></i> Your Pending Categories</h3>
                    <?php foreach ($user_pending_categories as $pending_cat): ?>
                        <div style="background: rgba(245, 158, 11, 0.1); padding: 15px; border-radius: 8px; margin: 10px 0;">
                            <strong><?php echo htmlspecialchars($pending_cat['name']); ?></strong> - 
                            Submitted <?php echo time_ago($pending_cat['created_at']); ?>
                            <span class="status-badge status-pending">Pending Approval</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Categories Grid -->
            <?php if (empty($categories)): ?>
                <div style="text-align: center; padding: 60px 20px;">
                    <i class="fas fa-tags fa-3x" style="color: var(--text-secondary); margin-bottom: 20px;"></i>
                    <h3>No categories found</h3>
                    <p><?php echo $status_filter === 'active' ? 'Be the first to create a category!' : 'No categories match your current filters.'; ?></p>
                </div>
            <?php else: ?>
                <div class="category-grid">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-card">
                            <div class="category-header">
                                <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                                <?php if ($category['description']): ?>
                                    <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="category-meta">
                                <div>
                                    Created by <strong><?php echo htmlspecialchars($category['creator_name']); ?></strong>
                                    <?php if ($category['approver_name']): ?>
                                        <br>Approved by <strong><?php echo htmlspecialchars($category['approver_name']); ?></strong>
                                    <?php endif; ?>
                                </div>
                                <span class="status-badge status-<?php echo $category['status']; ?>">
                                    <?php echo ucfirst($category['status']); ?>
                                </span>
                            </div>
                            
                            <div class="category-stats">
                                <div class="stat-item">
                                    <i class="fas fa-comments"></i>
                                    <span><?php echo $category['post_count']; ?> posts</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo time_ago($category['created_at']); ?></span>
                                </div>
                            </div>
                            
                            <div style="margin-top: 20px; text-align: center;">
                                <a href="category.php?id=<?php echo $category['id']; ?>" class="btn btn-outline" style="width: 100%;">
                                    View Category
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Create Category Modal -->
    <?php if ($current_user): ?>
        <div id="createCategoryModal" class="create-category-modal">
            <div class="modal-content">
                <button class="close-modal" onclick="closeCreateModal()">&times;</button>
                <h2><i class="fas fa-plus-circle"></i> Create New Category</h2>
                
                <form method="POST" id="createCategoryForm">
                    <div class="form-group">
                        <label for="category_name">Category Name *</label>
                        <input type="text" id="category_name" name="category_name" 
                               placeholder="Enter category name" 
                               maxlength="50" required>
                        <small>Maximum 50 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_description">Description</label>
                        <textarea id="category_description" name="category_description" 
                                  placeholder="Describe your category..." 
                                  maxlength="500" rows="4"></textarea>
                        <small>Maximum 500 characters</small>
                    </div>
                    
                    <div class="form-group" style="display: flex; gap: 15px; margin-top: 25px;">
                        <button type="submit" name="create_category" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-plus"></i> Create Category
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeCreateModal()" style="flex: 1;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        function openCreateModal() {
            document.getElementById('createCategoryModal').style.display = 'flex';
        }
        
        function closeCreateModal() {
            document.getElementById('createCategoryModal').style.display = 'none';
        }
        
        function applyFilters() {
            const status = document.getElementById('status-filter').value;
            const sort = document.getElementById('sort-filter').value;
            const url = new URL(window.location);
            url.searchParams.set('status', status);
            url.searchParams.set('sort', sort);
            window.location.href = url.toString();
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('createCategoryModal');
            if (event.target === modal) {
                closeCreateModal();
            }
        }
        
        // Form validation
        document.getElementById('createCategoryForm')?.addEventListener('submit', function(e) {
            const name = document.getElementById('category_name').value.trim();
            if (name.length === 0) {
                e.preventDefault();
                alert('Please enter a category name.');
                return;
            }
            if (name.length > 50) {
                e.preventDefault();
                alert('Category name must be 50 characters or less.');
                return;
            }
        });
    </script>
</body>
</html>