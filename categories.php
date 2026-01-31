<?php
// Categories Page - User Created Categories
require_once 'config.php';
require_once 'includes/functions.php';

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
if (isset($_POST['create_category']) && $current_user) {
    try {
        $name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['category_description'] ?? '');
        $color = $_POST['category_color'] ?? '#007bff';
        $icon = $_POST['category_icon'] ?? 'folder';
        
        // Validate inputs
        if (empty($name)) {
            throw new Exception('Category name is required');
        }
        
        if (strlen($name) > 50) {
            throw new Exception('Category name must be 50 characters or less');
        }
        
        if (strlen($description) > 500) {
            throw new Exception('Description must be 500 characters or less');
        }
        
        // Check if category already exists
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            throw new Exception('A category with this name already exists');
        }
        
        // Check KYC requirement for verified users only
        $kyc_required = get_setting('kyc_required_for_categories', '0') === '1';
        if ($kyc_required && (!isset($current_user['kyc_status']) || $current_user['kyc_status'] !== 'verified')) {
            throw new Exception('You need to be KYC verified to create categories');
        }
        
        // Insert category directly as active
        $stmt = $pdo->prepare("INSERT INTO categories (name, description, color, icon, created_by, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$name, $description, $color, $icon, $current_user['id']]);
        
        $category_id = $pdo->lastInsertId();
        
        // Award EXP for creating category
        award_exp($current_user['id'], 50, "Created category: $name");
        
        $message = "Category '$name' created successfully!";
        $message_type = 'success';
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'active';
$sort_by = $_GET['sort'] ?? 'created_at';
$search_term = trim($_GET['search'] ?? '');

// Build query
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "c.status = ?";
    $params[] = $status_filter;
}

if ($search_term) {
    $where_conditions[] = "(c.name LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get categories
$query = "SELECT c.*, u.username as creator_name 
          FROM categories c 
          LEFT JOIN users u ON c.created_by = u.id 
          $where_clause 
          ORDER BY " . ($sort_by === 'popularity' ? 'c.post_count DESC' : 'c.created_at DESC');

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's pending categories
$user_pending_categories = [];
if ($current_user) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE created_by = ? AND status = 'pending' ORDER BY created_at DESC");
    $stmt->execute([$current_user['id']]);
    $user_pending_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate statistics
$stats = [
    'active' => 0,
    'pending' => 0,
    'rejected' => 0,
    'total' => count($categories)
];

foreach ($categories as $category) {
    $stats[$category['status']] = ($stats[$category['status']] ?? 0) + 1;
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
        .categories-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .categories-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .categories-header h1 {
            font-family: 'Orbitron', monospace;
            color: var(--primary);
            text-shadow: 0 0 20px var(--primary);
            margin-bottom: 1rem;
        }
        
        .filters-bar {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .filter-select {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
        }
        
        .search-box {
            flex: 2;
            min-width: 300px;
        }
        
        .search-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            font-family: 'Orbitron', monospace;
            margin-bottom: 0.5rem;
        }
        
        .stat-active { color: var(--success); }
        .stat-pending { color: var(--warning); }
        .stat-rejected { color: var(--danger); }
        .stat-total { color: var(--primary); }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .category-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            border-color: var(--primary);
        }
        
        .category-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .category-name {
            font-family: 'Orbitron', monospace;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .category-icon {
            font-size: 1.5rem;
        }
        
        .category-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .category-meta {
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
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
        
        .create-category-btn {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .create-category-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 245, 255, 0.4);
        }
        
        .kyc-requirement, .pending-categories {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-container">
        <div class="container">
            <div class="categories-container">
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
                
                <!-- Filters Bar -->
                <div class="filters-bar">
                    <div class="search-box">
                        <label for="search">Search Categories</label>
                        <input type="text" id="search" name="search" class="search-input" 
                               placeholder="Search by name or description..." 
                               value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="status-filter">Status</label>
                        <select id="status-filter" class="filter-select">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort-filter">Sort By</label>
                        <select id="sort-filter" class="filter-select">
                            <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="popularity" <?php echo $sort_by === 'popularity' ? 'selected' : ''; ?>>Most Popular</option>
                        </select>
                    </div>
                    
                    <?php if ($current_user): ?>
                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <button class="create-category-btn" onclick="openCreateModal()">
                                <i class="fas fa-plus"></i> Create Category
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                
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
                
                <!-- KYC Requirement Notice -->
                <?php if ($current_user && get_setting('kyc_required_for_categories', '0') === '1' && (!isset($current_user['kyc_status']) || $current_user['kyc_status'] !== 'verified')): ?>
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
                    <div class="empty-state">
                        <i class="fas fa-tags fa-3x"></i>
                        <h3>No categories found</h3>
                        <p><?php echo $status_filter === 'active' ? 'Be the first to create a category!' : 'No categories match your current filters.'; ?></p>
                    </div>
                <?php else: ?>
                    <div class="category-grid">
                        <?php foreach ($categories as $category): ?>
                            <div class="category-card">
                                <div class="category-header">
                                    <h3 class="category-name">
                                        <i class="fas fa-<?php echo htmlspecialchars($category['icon']); ?> category-icon" 
                                           style="color: <?php echo htmlspecialchars($category['color']); ?>;"></i>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </h3>
                                    <?php if ($category['description']): ?>
                                        <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="category-meta">
                                    <div>
                                        Created by <strong><?php echo htmlspecialchars($category['creator_name'] ?? 'System'); ?></strong>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('M j, Y', strtotime($category['created_at'])); ?>
                                    </div>
                                    <div>
                                        <span class="status-badge status-<?php echo $category['status']; ?>">
                                            <?php echo ucfirst($category['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Create Category Modal -->
    <?php if ($current_user): ?>
        <div class="modal" id="createCategoryModal">
            <div class="modal-content">
                <button class="close-modal" onclick="closeCreateModal()">&times;</button>
                <h2>Create New Category</h2>
                <form method="POST" id="createCategoryForm">
                    <div class="form-group">
                        <label for="category_name">Category Name *</label>
                        <input type="text" id="category_name" name="category_name" class="form-input" required maxlength="50">
                    </div>
                    
                    <div class="form-group">
                        <label for="category_description">Description</label>
                        <textarea id="category_description" name="category_description" class="form-textarea" 
                                  maxlength="500" placeholder="Describe your category..."></textarea>
                        <small id="desc-count">0/500 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_color">Color</label>
                        <input type="color" id="category_color" name="category_color" class="form-input" value="#007bff">
                    </div>
                    
                    <div class="form-group">
                        <label for="category_icon">Icon</label>
                        <select id="category_icon" name="category_icon" class="form-select">
                            <option value="folder">Folder</option>
                            <option value="comments">Comments</option>
                            <option value="users">Users</option>
                            <option value="gamepad">Gaming</option>
                            <option value="music">Music</option>
                            <option value="film">Movies</option>
                            <option value="book">Books</option>
                            <option value="laptop">Technology</option>
                            <option value="utensils">Food</option>
                            <option value="heart">Health</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="create_category" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Category
                        </button>
                        <button type="button" class="btn btn-outline" onclick="closeCreateModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Filter functionality
        document.getElementById('status-filter').addEventListener('change', function() {
            updateFilters();
        });
        
        document.getElementById('sort-filter').addEventListener('change', function() {
            updateFilters();
        });
        
        document.getElementById('search').addEventListener('input', function() {
            // Debounce search
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(updateFilters, 500);
        });
        
        function updateFilters() {
            const status = document.getElementById('status-filter').value;
            const sort = document.getElementById('sort-filter').value;
            const search = document.getElementById('search').value;
            
            let url = new URL(window.location);
            if (status !== 'all') url.searchParams.set('status', status);
            else url.searchParams.delete('status');
            
            if (sort !== 'created_at') url.searchParams.set('sort', sort);
            else url.searchParams.delete('sort');
            
            if (search) url.searchParams.set('search', search);
            else url.searchParams.delete('search');
            
            window.location.href = url.toString();
        }
        
        // Modal functionality
        function openCreateModal() {
            document.getElementById('createCategoryModal').classList.add('active');
        }
        
        function closeCreateModal() {
            document.getElementById('createCategoryModal').classList.remove('active');
        }
        
        // Character counter for description
        document.getElementById('category_description')?.addEventListener('input', function() {
            const count = this.value.length;
            document.getElementById('desc-count').textContent = `${count}/500 characters`;
        });
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('createCategoryModal');
            if (e.target === modal) {
                closeCreateModal();
            }
        });
    </script>
</body>
</html>