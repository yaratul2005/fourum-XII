<?php
require_once 'config.php';
redirect_if_not_logged_in();

$errors = [];
$success = '';

// Get categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize_input($_POST['title'] ?? '');
    $content = $_POST['content'] ?? ''; // Don't sanitize rich text content
    $category = sanitize_input($_POST['category'] ?? 'general');
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Title is required';
    } elseif (strlen($title) < 5) {
        $errors[] = 'Title must be at least 5 characters';
    } elseif (strlen($title) > 200) {
        $errors[] = 'Title must be less than 200 characters';
    }
    
    if (empty($content)) {
        $errors[] = 'Content is required';
    } elseif (strlen(strip_tags($content)) < 10) {
        $errors[] = 'Content must be at least 10 characters';
    }
    
    if (empty($category)) {
        $errors[] = 'Category is required';
    }
    
    // Validate category exists
    $valid_categories = array_column($categories, 'name');
    if (!in_array($category, $valid_categories)) {
        $errors[] = 'Invalid category selected';
    }
    
    // Create post if no errors
    if (empty($errors)) {
        $post_id = create_post(get_current_user_id(), $title, $content, $category);
        
        if ($post_id) {
            $success = 'Post created successfully!';
            // Redirect to the new post after a short delay
            header("refresh:2;url=post.php?id=$post_id");
        } else {
            $errors[] = 'Failed to create post. Please try again.';
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
    <!-- Quill.js Rich Text Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        /* Rich Text Editor Custom Styles */
        .ql-toolbar {
            border: 1px solid var(--border-color);
            border-radius: 8px 8px 0 0;
            background: var(--card-bg);
        }
        
        .ql-container {
            border: 1px solid var(--border-color);
            border-radius: 0 0 8px 8px;
            border-top: none;
            background: var(--card-bg);
            color: var(--text-primary);
            font-family: inherit;
        }
        
        .ql-editor {
            min-height: 200px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .ql-editor p {
            margin: 0 0 1rem 0;
            line-height: 1.6;
        }
        
        .ql-editor h1, .ql-editor h2, .ql-editor h3 {
            margin: 1.5rem 0 1rem 0;
            color: var(--primary);
        }
        
        .ql-editor ul, .ql-editor ol {
            margin: 1rem 0;
            padding-left: 2rem;
        }
        
        .ql-editor li {
            margin: 0.25rem 0;
        }
        
        .ql-editor a {
            color: var(--primary);
            text-decoration: underline;
        }
        
        .ql-editor blockquote {
            border-left: 3px solid var(--primary);
            margin: 1rem 0;
            padding-left: 1rem;
            color: var(--text-secondary);
            font-style: italic;
        }
        
        .ql-editor code {
            background: rgba(0, 0, 0, 0.3);
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        
        .ql-editor pre {
            background: rgba(0, 0, 0, 0.3);
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
        }
        
        .editor-toolbar {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
        }
        
        .editor-action-btn {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        
        .editor-action-btn:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: var(--darker-bg);
        }
        
        .editor-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        
        /* Preview Modal Styles */
        #preview-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        #preview-content .ql-syntax {
            background: rgba(0, 0, 0, 0.3);
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="cyber-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1><i class="fas fa-robot"></i> FUROM</h1>
                    <span class="tagline">Futuristic Community Platform</span>
                </div>
                <nav class="main-nav">
                    <a href="index.php" class="nav-link">Home</a>
                </nav>
                <div class="user-actions">
                    <?php $current_user = get_user_data(get_current_user_id()); ?>
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <img src="<?php echo $current_user['avatar'] ?: 'assets/images/default-avatar.png'; ?>" 
                                 alt="Avatar" class="avatar-small">
                            <span class="username"><?php echo $current_user['username']; ?></span>
                            <span class="exp-badge"><?php echo format_number($current_user['exp']); ?> EXP</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        <div class="container">
            <div class="form-container">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                    <h2><i class="fas fa-plus-circle"></i> Create New Post</h2>
                    <div style="margin-left: auto; background: linear-gradient(45deg, var(--secondary), var(--accent)); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem;">
                        <i class="fas fa-bolt"></i> Earn <?php echo EXP_POST; ?> EXP for posting
                    </div>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="error-messages" style="background: rgba(255, 71, 87, 0.2); border: 1px solid var(--danger); border-radius: 10px; padding: 1rem; margin-bottom: 1rem;">
                        <h4 style="color: var(--danger); margin-bottom: 0.5rem;"><i class="fas fa-exclamation-circle"></i> Please fix the following errors:</h4>
                        <ul style="color: var(--text-secondary);">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message" style="background: rgba(0, 255, 157, 0.2); border: 1px solid var(--success); border-radius: 10px; padding: 1rem; margin-bottom: 1rem; color: var(--success);">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <p style="margin-top: 0.5rem; font-size: 0.9rem;">Redirecting to your post...</p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" data-validate data-auto-save="true" id="create-post-form">
                    <div class="form-group">
                        <label for="title" class="form-label">Post Title *</label>
                        <input type="text" id="title" name="title" class="form-input" 
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                               required minlength="5" maxlength="200"
                               placeholder="Enter a compelling title for your post...">
                        <div style="display: flex; justify-content: space-between; margin-top: 0.5rem;">
                            <small style="color: var(--text-secondary); font-size: 0.8rem;">
                                5-200 characters
                            </small>
                            <small id="title-counter" style="color: var(--text-secondary); font-size: 0.8rem;">
                                0/200
                            </small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="category" class="form-label">Category *</label>
                        <div class="select-wrapper">
                            <select id="category" name="category" class="form-input form-select" required>
                                <option value="">Select a category</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat['name']; ?>" 
                                            <?php echo (($_POST['category'] ?? '') === $cat['name']) ? 'selected' : ''; ?>
                                            style="background: <?php echo $cat['color']; ?>; color: white;">
                                        <i class="fas fa-<?php echo $cat['icon']; ?>"></i>
                                        <?php echo ucfirst($cat['name']); ?> - <?php echo $cat['description']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <small style="color: var(--text-secondary); font-size: 0.8rem;">
                            Choose the most relevant category for your post
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="content" class="form-label">Content *</label>
                        
                        <!-- Rich Text Editor Toolbar -->
                        <div class="editor-toolbar">
                            <button type="button" class="editor-action-btn" id="bold-btn" title="Bold">
                                <i class="fas fa-bold"></i>
                            </button>
                            <button type="button" class="editor-action-btn" id="italic-btn" title="Italic">
                                <i class="fas fa-italic"></i>
                            </button>
                            <button type="button" class="editor-action-btn" id="underline-btn" title="Underline">
                                <i class="fas fa-underline"></i>
                            </button>
                            <button type="button" class="editor-action-btn" id="link-btn" title="Insert Link">
                                <i class="fas fa-link"></i>
                            </button>
                            <button type="button" class="editor-action-btn" id="image-btn" title="Insert Image">
                                <i class="fas fa-image"></i>
                            </button>
                            <button type="button" class="editor-action-btn" id="code-btn" title="Code Block">
                                <i class="fas fa-code"></i>
                            </button>
                            <button type="button" class="editor-action-btn" id="blockquote-btn" title="Blockquote">
                                <i class="fas fa-quote-right"></i>
                            </button>
                        </div>
                        
                        <!-- Hidden textarea for form submission -->
                        <textarea id="content" name="content" style="display: none;"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                        
                        <!-- Quill Editor Container -->
                        <div id="editor-container" style="height: 300px;">
                            <?php echo $_POST['content'] ?? ''; ?>
                        </div>
                        
                        <div class="editor-stats">
                            <small id="content-stats">Characters: 0 | Words: 0</small>
                            <small style="color: var(--text-secondary); font-size: 0.8rem;">
                                Minimum 10 characters
                            </small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <button type="submit" class="btn btn-primary" style="flex: 1; min-width: 200px;">
                                <i class="fas fa-paper-plane"></i> Publish Post
                            </button>
                            <button type="button" id="preview-btn" class="btn btn-outline" style="flex: 1; min-width: 200px;">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                            <button type="button" id="clear-btn" class="btn btn-danger" style="flex: 1; min-width: 200px;">
                                <i class="fas fa-trash"></i> Clear Content
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Preview Modal -->
                <div id="preview-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
                    <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 15px; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto; position: relative;">
                        <div style="padding: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <h3><i class="fas fa-eye"></i> Post Preview</h3>
                            <button id="close-preview" style="background: none; border: none; color: var(--text-primary); font-size: 1.5rem; cursor: pointer;">&times;</button>
                        </div>
                        <div id="preview-content" style="padding: 1.5rem;"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="cyber-footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Furom. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
        // Initialize Quill Editor
        const quill = new Quill('#editor-container', {
            theme: 'snow',
            modules: {
                toolbar: false // We'll use custom toolbar
            },
            placeholder: 'Share your thoughts, questions, or ideas with the community...'
        });
        
        // Set initial content
        const initialContent = document.getElementById('content').value;
        if (initialContent) {
            quill.root.innerHTML = initialContent;
        }
        
        // Update hidden textarea on content change
        quill.on('text-change', function() {
            document.getElementById('content').value = quill.root.innerHTML;
            updateEditorStats();
        });
        
        // Custom toolbar buttons
        document.getElementById('bold-btn').addEventListener('click', function() {
            quill.format('bold', !quill.getFormat().bold);
        });
        
        document.getElementById('italic-btn').addEventListener('click', function() {
            quill.format('italic', !quill.getFormat().italic);
        });
        
        document.getElementById('underline-btn').addEventListener('click', function() {
            quill.format('underline', !quill.getFormat().underline);
        });
        
        document.getElementById('link-btn').addEventListener('click', function() {
            const url = prompt('Enter URL:');
            if (url) {
                quill.format('link', url);
            }
        });
        
        document.getElementById('image-btn').addEventListener('click', function() {
            const url = prompt('Enter image URL:');
            if (url) {
                const range = quill.getSelection();
                quill.insertEmbed(range.index, 'image', url);
            }
        });
        
        document.getElementById('code-btn').addEventListener('click', function() {
            const range = quill.getSelection();
            if (range) {
                quill.formatLine(range.index, range.length, 'code-block', true);
            }
        });
        
        document.getElementById('blockquote-btn').addEventListener('click', function() {
            const range = quill.getSelection();
            if (range) {
                quill.formatLine(range.index, range.length, 'blockquote', true);
            }
        });
        
        // Update editor statistics
        function updateEditorStats() {
            const text = quill.getText();
            const charCount = text.length - 1; // Subtract 1 for newline
            const wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;
            
            document.getElementById('content-stats').textContent = 
                `Characters: ${charCount} | Words: ${wordCount}`;
        }
        
        // Character counter for title
        document.getElementById('title').addEventListener('input', function() {
            const counter = document.getElementById('title-counter');
            counter.textContent = `${this.value.length}/200`;
            
            if (this.value.length > 180) {
                counter.style.color = 'var(--warning)';
            } else if (this.value.length > 190) {
                counter.style.color = 'var(--danger)';
            } else {
                counter.style.color = 'var(--text-secondary)';
            }
        });
        
        // Preview functionality
        document.getElementById('preview-btn').addEventListener('click', function() {
            const title = document.getElementById('title').value;
            const content = quill.root.innerHTML;
            const category = document.getElementById('category').value;
            
            if (!title.trim() || !content.trim()) {
                alert('Please enter both title and content to preview');
                return;
            }
            
            const previewContent = document.getElementById('preview-content');
            const categoryName = document.querySelector(`#category option[value="${category}"]`)?.text.split(' - ')[0] || 'General';
            
            previewContent.innerHTML = `
                <div style="margin-bottom: 1rem; padding: 1rem; background: rgba(0, 245, 255, 0.1); border-radius: 10px;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span style="background: var(--primary); color: var(--darker-bg); padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem; font-weight: 500;">
                            ${categoryName}
                        </span>
                        <span style="color: var(--text-secondary); font-size: 0.9rem;">
                            by You • Just now
                        </span>
                    </div>
                    <h2 style="color: var(--text-primary); margin: 0.5rem 0; font-size: 1.5rem;">${title}</h2>
                </div>
                <div style="color: var(--text-secondary); line-height: 1.7;">${content}</div>
            `;
            
            document.getElementById('preview-modal').style.display = 'flex';
        });
        
        // Clear content functionality
        document.getElementById('clear-btn').addEventListener('click', function() {
            if (confirm('Are you sure you want to clear all content?')) {
                quill.setContents([{ insert: '\n' }]);
                document.getElementById('title').value = '';
                document.getElementById('category').selectedIndex = 0;
                updateEditorStats();
            }
        });
        
        // Close preview modal
        document.getElementById('close-preview').addEventListener('click', function() {
            document.getElementById('preview-modal').style.display = 'none';
        });
        
        // Close modal when clicking outside
        document.getElementById('preview-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
        
        // Auto-save notification
        document.addEventListener('DOMContentLoaded', function() {
            // Check if there's auto-saved data
            const savedData = localStorage.getItem('autosave_create-post-form');
            if (savedData) {
                showNotification('Draft loaded from auto-save', 'info');
            }
            
            // Initialize stats
            updateEditorStats();
        });
        
        // Form submission validation
        document.getElementById('create-post-form').addEventListener('submit', function(e) {
            const content = quill.root.innerHTML;
            const textContent = quill.getText().trim();
            
            if (textContent.length < 10) {
                e.preventDefault();
                alert('Content must be at least 10 characters long');
                return false;
            }
            
            // Update the hidden textarea with final content
            document.getElementById('content').value = content;
        });
    </script>
    <!-- Quill.js Rich Text Editor -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>