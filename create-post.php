<?php
require_once 'config.php';
require_once 'includes/functions.php';

redirect_if_not_logged_in();

$errors = [];
$success = '';

// Get available categories
$stmt = $pdo->prepare("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add default "Unknown" category if none exist
if (empty($categories)) {
    $categories[] = ['id' => 0, 'name' => 'Unknown'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    
    // Validate inputs
    if (empty($title)) {
        $errors[] = 'Title is required';
    } elseif (strlen($title) > 200) {
        $errors[] = 'Title must be 200 characters or less';
    }
    
    if (empty($content)) {
        $errors[] = 'Content is required';
    } elseif (strlen($content) > 10000) {
        $errors[] = 'Content must be 10,000 characters or less';
    }
    
    // Validate category
    if ($category_id > 0) {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND status = 'active'");
        $stmt->execute([$category_id]);
        if (!$stmt->fetch()) {
            $errors[] = 'Invalid category selected';
        }
    } else {
        // Use Unknown category (ID 0) if no valid category selected
        $category_id = 0;
    }
    
    if (empty($errors)) {
        try {
            $user_id = get_current_user_id();
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, category_id, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$user_id, $title, $content, $category_id]);
            
            $post_id = $pdo->lastInsertId();
            
            // Award EXP for creating post
            award_exp($user_id, EXP_POST_CREATE, "Created post: $title");
            
            $success = 'Post created successfully!';
            
            // Redirect to the new post
            header("Location: post.php?id=$post_id");
            exit();
            
        } catch (Exception $e) {
            $errors[] = 'Failed to create post: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .create-post-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .form-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .form-header h1 {
            font-family: 'Orbitron', monospace;
            color: var(--primary);
            text-shadow: 0 0 20px var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 0.75rem;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background: rgba(18, 18, 37, 0.5);
            color: var(--text-primary);
            font-family: inherit;
            transition: all 0.3s ease;
        }
        
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 15px rgba(0, 245, 255, 0.3);
        }
        
        .form-textarea {
            min-height: 200px;
            resize: vertical;
            font-family: 'Exo 2', sans-serif;
        }
        
        .char-counter {
            text-align: right;
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .category-select {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .category-info {
            background: rgba(0, 245, 255, 0.1);
            border: 1px solid var(--primary);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        @media (max-width: 768px) {
            .create-post-container {
                padding: 0 0.5rem;
            }
            
            .form-card {
                padding: 1.5rem;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .category-select {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-container">
        <div class="container">
            <div class="create-post-container">
                <div class="form-card">
                    <div class="form-header">
                        <h1><i class="fas fa-plus-circle"></i> Create New Post</h1>
                        <p>Share your thoughts with the community</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert error">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="createPostForm">
                        <div class="form-group">
                            <label for="title" class="form-label">Post Title *</label>
                            <input type="text" id="title" name="title" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                   maxlength="200" required>
                            <div class="char-counter">
                                <span id="title-count">0</span>/200 characters
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id" class="form-label">Category</label>
                            <div class="category-select">
                                <select id="category_id" name="category_id" class="form-select">
                                    <option value="0" <?php echo (($_POST['category_id'] ?? 0) == 0) ? 'selected' : ''; ?>>
                                        Unknown (Default)
                                    </option>
                                    <?php foreach ($categories as $category): ?>
                                        <?php if ($category['id'] > 0): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo (($_POST['category_id'] ?? 0) == $category['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="category-info">
                                <i class="fas fa-info-circle"></i> 
                                Posts will be assigned to "Unknown" category by default. You can select a specific category or edit this later.
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="content" class="form-label">Content *</label>
                            <textarea id="content" name="content" class="form-textarea" 
                                      placeholder="Write your post content here..." required><?php 
                                echo htmlspecialchars($_POST['content'] ?? ''); 
                            ?></textarea>
                            <div class="char-counter">
                                <span id="content-count">0</span>/10000 characters
                            </div>
                        </div>
                        
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Publish Post
                            </button>
                            <a href="index.php" class="btn btn-outline">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Character counters
        document.getElementById('title')?.addEventListener('input', function() {
            const count = this.value.length;
            document.getElementById('title-count').textContent = count;
            
            // Visual feedback for approaching limit
            if (count > 180) {
                document.getElementById('title-count').style.color = '#ffcc00';
            } else if (count > 190) {
                document.getElementById('title-count').style.color = '#ff4757';
            } else {
                document.getElementById('title-count').style.color = '';
            }
        });
        
        document.getElementById('content')?.addEventListener('input', function() {
            const count = this.value.length;
            document.getElementById('content-count').textContent = count;
            
            // Visual feedback for approaching limit
            if (count > 9000) {
                document.getElementById('content-count').style.color = '#ffcc00';
            } else if (count > 9500) {
                document.getElementById('content-count').style.color = '#ff4757';
            } else {
                document.getElementById('content-count').style.color = '';
            }
        });
        
        // Initialize counters with existing values
        window.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('title');
            const contentInput = document.getElementById('content');
            
            if (titleInput) {
                document.getElementById('title-count').textContent = titleInput.value.length;
            }
            
            if (contentInput) {
                document.getElementById('content-count').textContent = contentInput.value.length;
            }
        });
    </script>
</body>
</html>