<?php
require_once '../config.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    header('Location: ../login.php');
    exit();
}

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);
        $color = sanitize_input($_POST['color']);
        $icon = sanitize_input($_POST['icon']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description, color, icon) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $description, $color, $icon])) {
                $message = 'Category added successfully!';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Error adding category: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['update_category'])) {
        $id = intval($_POST['id']);
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);
        $color = sanitize_input($_POST['color']);
        $icon = sanitize_input($_POST['icon']);
        
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, color = ?, icon = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $color, $icon, $id])) {
                $message = 'Category updated successfully!';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Error updating category: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['delete_category'])) {
        $id = intval($_POST['id']);
        
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = 'Category deleted successfully!';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Error deleting category: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Fetch categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .category-card {
            background: var(--admin-card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid var(--admin-border);
            transition: var(--admin-transition);
        }
        
        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--admin-shadow);
        }
        
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--admin-border);
        }
        
        .category-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            color: white;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .color-preview {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .stat-card {
            background: var(--admin-darker);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: var(--admin-bg);
            border-radius: 15px;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            border: 1px solid var(--admin-border);
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1><i class="fas fa-tags"></i> Categories Management</h1>
                <button class="btn btn-primary" onclick="openModal('addModal')">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Overview -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo count($categories); ?></h3>
                    <p>Total Categories</p>
                </div>
                <div class="stat-card">
                    <h3><?php 
                        $stmt = $pdo->query("SELECT COUNT(*) FROM posts");
                        echo $stmt->fetchColumn();
                    ?></h3>
                    <p>Total Posts</p>
                </div>
                <div class="stat-card">
                    <h3><?php 
                        $stmt = $pdo->query("SELECT COUNT(DISTINCT category) FROM posts");
                        echo $stmt->fetchColumn();
                    ?></h3>
                    <p>Active Categories</p>
                </div>
            </div>

            <!-- Categories List -->
            <div class="admin-content">
                <?php foreach ($categories as $category): ?>
                    <div class="category-card">
                        <div class="category-header">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div class="category-badge" style="background: <?php echo htmlspecialchars($category['color']); ?>">
                                    <i class="fas fa-<?php echo htmlspecialchars($category['icon']); ?>"></i>
                                    <?php echo htmlspecialchars(ucfirst($category['name'])); ?>
                                </div>
                                <div class="color-preview" style="background: <?php echo htmlspecialchars($category['color']); ?>"></div>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline" onclick="editCategory(<?php echo $category['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                        <p><?php echo htmlspecialchars($category['description']); ?></p>
                        <small style="color: var(--admin-text-secondary);">
                            <i class="fas fa-hashtag"></i> <?php 
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE category = ?");
                                $stmt->execute([$category['name']]);
                                echo $stmt->fetchColumn();
                            ?> posts
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Add Category Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <h2><i class="fas fa-plus-circle"></i> Add New Category</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Color</label>
                        <input type="color" name="color" class="form-control" value="#00f5ff">
                    </div>
                    <div class="form-group">
                        <label>Icon Class</label>
                        <input type="text" name="icon" class="form-control" placeholder="e.g., robot" required>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" name="add_category" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h2><i class="fas fa-edit"></i> Edit Category</h2>
            <form method="POST" id="editForm">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="editName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="editDescription" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Color</label>
                        <input type="color" name="color" id="editColor" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Icon Class</label>
                        <input type="text" name="icon" id="editIcon" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" name="update_category" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Category
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <h2><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h2>
            <p>Are you sure you want to delete the category "<span id="deleteCategoryName"></span>"?</p>
            <p style="color: var(--admin-warning); font-weight: 500;">
                <i class="fas fa-info-circle"></i> This will affect all posts in this category!
            </p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="id" id="deleteId">
                <div class="form-group">
                    <button type="submit" name="delete_category" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Yes, Delete
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function editCategory(id) {
            // Fetch category data (in a real implementation, you'd use AJAX)
            // For now, we'll just open the modal
            document.getElementById('editId').value = id;
            openModal('editModal');
        }
        
        function deleteCategory(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteCategoryName').textContent = name;
            openModal('deleteModal');
        }
        
        // Close modals when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>