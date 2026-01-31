<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Check admin access
if (!is_logged_in() || get_user_data(get_current_user_id())['username'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Initialize settings array
$settings = [
    'site_name' => SITE_NAME,
    'site_description' => 'A futuristic community forum',
    'admin_email' => ADMIN_EMAIL,
    'maintenance_mode' => false,
    'registration_enabled' => true,
    'email_verification_required' => true,
    'default_user_exp' => 0,
    'exp_per_post' => EXP_POST,
    'exp_per_comment' => EXP_COMMENT,
    'exp_per_upvote' => EXP_UPVOTE,
    'exp_per_downvote' => EXP_DOWNVOTE,
    'posts_per_page' => 10,
    'comments_per_page' => 20,
    'max_post_length' => 5000,
    'max_comment_length' => 1000,
    'allow_guest_viewing' => false,
    'enable_reporting' => true,
    'enable_voting' => true,
    'enable_categories' => true,
    'default_timezone' => 'UTC',
    'session_timeout' => SESSION_TIMEOUT,
    'max_avatar_size' => 2097152, // 2MB
    'allowed_avatar_types' => 'jpg,jpeg,png,gif',
    'backup_frequency' => 'daily',
    'auto_prune_deleted_content' => 30, // days
    'spam_threshold' => 5, // reports before auto-action
    'rate_limit_posts' => 10, // per hour
    'rate_limit_comments' => 30, // per hour
    'custom_css' => '',
    'custom_js' => ''
];

// Load existing settings from database (if settings table exists)
try {
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    if ($stmt) {
        $db_settings = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($db_settings) {
            $settings = array_merge($settings, $db_settings);
        }
    }
} catch(Exception $e) {
    // Settings table doesn't exist yet, use defaults
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updated_settings = [];
    
    // Process all settings
    foreach($settings as $key => $default_value) {
        if (isset($_POST[$key])) {
            $value = $_POST[$key];
            
            // Convert boolean values
            if (in_array($key, ['maintenance_mode', 'registration_enabled', 'email_verification_required', 
                              'allow_guest_viewing', 'enable_reporting', 'enable_voting', 'enable_categories'])) {
                $value = isset($_POST[$key]) ? 1 : 0;
            }
            
            // Convert numeric values
            if (in_array($key, ['default_user_exp', 'exp_per_post', 'exp_per_comment', 'exp_per_upvote', 
                              'exp_per_downvote', 'posts_per_page', 'comments_per_page', 'max_post_length', 
                              'max_comment_length', 'session_timeout', 'max_avatar_size', 'auto_prune_deleted_content', 
                              'spam_threshold', 'rate_limit_posts', 'rate_limit_comments'])) {
                $value = intval($value);
            }
            
            $updated_settings[$key] = $value;
        }
    }
    
    // Save settings to database
    try {
        // Create settings table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Update each setting
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP");
        
        foreach($updated_settings as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        
        $message = "Settings updated successfully!";
        $message_type = "success";
        
        // Update current settings array
        $settings = array_merge($settings, $updated_settings);
        
    } catch(Exception $e) {
        $message = "Error updating settings: " . $e->getMessage();
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - <?php echo SITE_NAME; ?> Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .settings-container {
            padding: 1.5rem;
        }
        
        .settings-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--admin-border);
            padding-bottom: 1rem;
        }
        
        .tab-button {
            background: var(--admin-card-bg);
            border: 1px solid var(--admin-border);
            border-radius: 6px 6px 0 0;
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            transition: var(--admin-transition);
            color: var(--admin-text-secondary);
            font-weight: 500;
        }
        
        .tab-button.active {
            background: var(--admin-primary);
            color: white;
            border-color: var(--admin-primary);
        }
        
        .tab-button:hover:not(.active) {
            background: var(--admin-darker);
            color: var(--admin-text-primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .settings-section {
            background: var(--admin-card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--admin-border);
        }
        
        .settings-section h3 {
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--admin-border);
            color: var(--admin-text-primary);
            font-size: 1.2rem;
        }
        
        .setting-group {
            margin-bottom: 1.5rem;
        }
        
        .setting-group:last-child {
            margin-bottom: 0;
        }
        
        .setting-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--admin-text-primary);
        }
        
        .setting-description {
            font-size: 0.9rem;
            color: var(--admin-text-secondary);
            margin-top: 0.25rem;
        }
        
        .setting-input {
            width: 100%;
            padding: 0.75rem;
            background: var(--admin-darker);
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            color: var(--admin-text-primary);
            font-family: inherit;
        }
        
        .setting-input:focus {
            outline: none;
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .setting-checkbox {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .setting-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .setting-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .settings-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--admin-border);
        }
        
        .reset-button {
            background: var(--admin-text-secondary);
            color: var(--admin-dark);
        }
        
        .reset-button:hover {
            background: var(--admin-text-primary);
        }
        
        .notification-banner {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .notification-banner.success {
            background: rgba(16, 185, 129, 0.15);
            color: var(--admin-success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .notification-banner.error {
            background: rgba(239, 68, 68, 0.15);
            color: var(--admin-danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .backup-section {
            background: var(--admin-darker);
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1rem;
            border: 1px solid var(--admin-border);
        }
        
        .backup-section h4 {
            color: var(--admin-text-primary);
            margin-bottom: 1rem;
        }
        
        .backup-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .backup-item {
            background: var(--admin-card-bg);
            padding: 1rem;
            border-radius: 6px;
            border: 1px solid var(--admin-border);
        }
        
        .backup-item .label {
            font-size: 0.9rem;
            color: var(--admin-text-secondary);
            margin-bottom: 0.25rem;
        }
        
        .backup-item .value {
            font-weight: 500;
            color: var(--admin-text-primary);
        }
        
        textarea.setting-input {
            min-height: 150px;
            font-family: monospace;
            font-size: 0.9rem;
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
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-flag"></i>
                    <span>Reports</span>
                </a>
                <a href="settings.php" class="nav-item active">
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
                    <h1><i class="fas fa-cog"></i> Site Settings</h1>
                    <div class="admin-info">
                        <span>Configure your forum settings</span>
                        <a href="../logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </header>

            <div class="settings-container">
                <?php if (isset($message)): ?>
                    <div class="notification-banner <?php echo $message_type; ?>">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Settings Tabs -->
                <div class="settings-tabs">
                    <button class="tab-button active" data-tab="general">General</button>
                    <button class="tab-button" data-tab="user">User Settings</button>
                    <button class="tab-button" data-tab="content">Content</button>
                    <button class="tab-button" data-tab="advanced">Advanced</button>
                    <button class="tab-button" data-tab="backup">Backup & Maintenance</button>
                </div>

                <form method="POST">
                    <!-- General Settings Tab -->
                    <div class="tab-content active" id="general-tab">
                        <div class="settings-section">
                            <h3><i class="fas fa-home"></i> Site Information</h3>
                            <div class="setting-grid">
                                <div class="setting-group">
                                    <label class="setting-label">Site Name</label>
                                    <input type="text" name="site_name" class="setting-input" 
                                           value="<?php echo htmlspecialchars($settings['site_name']); ?>">
                                    <div class="setting-description">The name displayed throughout your forum</div>
                                </div>
                                
                                <div class="setting-group">
                                    <label class="setting-label">Site Description</label>
                                    <input type="text" name="site_description" class="setting-input" 
                                           value="<?php echo htmlspecialchars($settings['site_description']); ?>">
                                    <div class="setting-description">Brief description of your forum</div>
                                </div>
                                
                                <div class="setting-group">
                                    <label class="setting-label">Admin Email</label>
                                    <input type="email" name="admin_email" class="setting-input" 
                                           value="<?php echo htmlspecialchars($settings['admin_email']); ?>">
                                    <div class="setting-description">Email for administrative notifications</div>
                                </div>
                                
                                <div class="setting-group">
                                    <label class="setting-label">Default Timezone</label>
                                    <select name="default_timezone" class="setting-input">
                                        <option value="UTC" <?php echo $settings['default_timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                        <option value="America/New_York" <?php echo $settings['default_timezone'] === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                        <option value="America/Chicago" <?php echo $settings['default_timezone'] === 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                        <option value="America/Denver" <?php echo $settings['default_timezone'] === 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                                        <option value="America/Los_Angeles" <?php echo $settings['default_timezone'] === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                    </select>
                                    <div class="setting-description">Default timezone for displaying dates</div>
                                </div>
                            </div>
                        </div>

                        <div class="settings-section">
                            <h3><i class="fas fa-toggle-on"></i> Site Status</h3>
                            <div class="setting-grid">
                                <div class="setting-group">
                                    <div class="setting-checkbox">
                                        <input type="checkbox" name="maintenance_mode" id="maintenance_mode" 
                                               <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                        <label class="setting-label" for="maintenance_mode">Maintenance Mode</label>
                                    </div>
                                    <div class="setting-description">Put site in maintenance mode for updates</div>
                                </div>
                                
                                <div class="setting-group">
                                    <div class="setting-checkbox">
                                        <input type="checkbox" name="registration_enabled" id="registration_enabled" 
                                               <?php echo $settings['registration_enabled'] ? 'checked' : ''; ?>>
                                        <label class="setting-label" for="registration_enabled">Enable Registration</label>
                                    </div>
                                    <div class="setting-description">Allow new user registrations</div>
                                </div>
                                
                                <div class="setting-group">
                                    <div class="setting-checkbox">
                                        <input type="checkbox" name="email_verification_required" id="email_verification_required" 
                                               <?php echo $settings['email_verification_required'] ? 'checked' : ''; ?>>
                                        <label class="setting-label" for="email_verification_required">Require Email Verification</label>
                                    </div>
                                    <div class="setting-description">New users must verify email before participating</div>
                                </div>
                                
                                <div class="setting-group">
                                    <div class="setting-checkbox">
                                        <input type="checkbox" name="allow_guest_viewing" id="allow_guest_viewing" 
                                               <?php echo $settings['allow_guest_viewing'] ? 'checked' : ''; ?>>
                                        <label class="setting-label" for="allow_guest_viewing">Allow Guest Viewing</label>
                                    </div>
                                    <div class="setting-description">Allow non-registered users to view content</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Settings Tab -->
                    <div class="tab-content" id="user-tab">
                        <div class="settings-section">
                            <h3><i class="fas fa-user"></i> User Experience</h3>
                            <div class="setting-grid">
                                <div class="setting-group">
                                    <label class="setting-label">Default User EXP</label>
                                    <input type="number" name="default_user_exp" class="setting-input" 
                                           value="<?php echo $settings['default_user_exp']; ?>" min="0">
                                    <div class="setting-description">Starting EXP for new users</div>
                                </div>
                                
                                <div class="setting-group">
                                    <label class="setting-label">Session Timeout (seconds)</label>
                                    <input type="number" name="session_timeout" class="setting-input" 
                                           value="<?php echo $settings['session_timeout']; ?>" min="300">
                                    <div class="setting-description">Time before users are logged out automatically</div>
                                </div>
                                
                                <div class="setting-group">
                                    <label class="setting-label">Max Avatar Size (bytes)</label>
                                    <input type="number" name="max_avatar_size" class="setting-input" 
                                           value="<?php echo $settings['max_avatar_size']; ?>" min="102400">
                                    <div class="setting-description">Maximum file size for user avatars (default: 2MB)</div>
                                </div>
                                
                                <div class="setting-group">
                                    <label class="setting-label">Allowed Avatar Types</label>
                                    <input type="text" name="allowed_avatar_types" class="setting-input" 
                                           value="<?php echo htmlspecialchars($settings['allowed_avatar_types']); ?>">
                                    <div class="setting-description">Comma-separated list of allowed file extensions</div>
                                </div>
                            </div>
                        </div>

                        <div class="settings-section">
                            <h3><i class="fas fa-chart-line"></i> Experience Points</h3>
                            <div class="setting-grid">
                                <div class="setting-group">
                                    <label class="setting-label">EXP per Post</label>
                                    <input type="number" name="exp_per_post" class="setting-input" 
                                           value="<?php echo $settings['exp_per_post']; ?>" min="0">
                                </div>
                                
                                <div class="setting-group">
                                    <label class="setting-label">EXP per Comment</label>
                                    <input type="number" name="exp_per_comment" class="setting-input" 
                                           value="<?php echo $settings['exp_per_comment']; ?>" min="0">
                                </div>
                                
                                <div class="setting-group">
                                    <label class="setting-label">EXP per Upvote</label>
                                    <input type="number" name="exp_per_upvote" class="setting-input" 
                                           value="<?php echo $settings['exp_per_upvote']; ?>" min="-100">
                                </div>
                                
                                <div class="setting-group">
                                    <label class="setting-label">EXP per Downvote</label>
                                    <input type="number" name="exp_per_downvote" class="setting-input" 
                                           value="<?php echo $settings['exp_per_downvote']; ?>" min="-100">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Settings Tab -->
                    <div class="tab-content" id="content-tab">
                        <div class="settings-section">
                            <h3><i class="fas fa-file-alt"></i> Content Limits</h3>
                            <div class="setting-grid">
                                <div class="setting-group">
                                    <label class="setting-label">Posts per Page</label>
                                    <input type="number" name="posts_per_page" class="setting-input" 
                                           value="<?php echo $settings['posts_per_page']; ?>" min="5" max="50">
                                </div>
                                
                                <div class="setting-group">
                                    <label class="setting-label">Comments per Page</label>
                                    <input type="number" name="comments_per_page" class="setting-input" 
                                           value="<?php echo $settings['comments_per_page']; ?>" min="10" max="100">
                                </div>
                                
                                <div class="setting-group">
                                    <label class="setting-label">Max Post Length</label>
                                    <input type="number" name="max_post_length" class="setting-input" 
                                           value="<?php echo $settings['max_post_length']; ?>" min="100" max="10000">
                                    <div class="setting-description">Maximum characters allowed in posts</div>
                                </div>
                                
                                <div class="setting-group">
                                    <label class="setting-label">Max Comment Length</label>
                                    <input type="number" name="max_comment_length" class="setting-input" 
                                           value="<?php echo $settings['max_comment_length']; ?>" min="10" max="5000">
                                    <div class="setting-description">Maximum characters allowed in comments</div>
                                </div>
                            </div>
                        </div>

                        <div class="settings-section">
                            <h3><i class="fas fa-shield-alt"></i> Content Moderation</h3>
                            <div class="setting-grid">
                                <div class="setting-group">
                                    <div class="setting-checkbox">
                                        <input type="checkbox" name="enable_reporting" id="enable_reporting" 
                                               <?php echo $settings['enable_reporting'] ? 'checked' : ''; ?>>
                                        <label class="setting-label" for="enable_reporting">Enable Reporting</label>
                                    </div>
                                    <div class="setting-description">Allow users to report inappropriate content</div>
                                </div>
                                
                                <div class="setting-group">
                                    <div class="setting-checkbox">
                                        <input type="checkbox" name="enable_voting" id="enable_voting" 
                                               <?php echo $settings['enable_voting'] ? 'checked' : ''; ?>>
                                        <label class="setting-label" for="enable_voting">Enable Voting</label>
                                    </div>
                                    <div class="setting-description">Allow users to upvote/downvote content</div>
                                </div>
                                
                                <div class="setting-group">
                                    <div class="setting-checkbox">
                                        <input type="checkbox" name="enable_categories" id="enable_categories" 
                                               <?php echo $settings['enable_categories'] ? 'checked' : ''; ?>>
                                        <label class="setting-label" for="enable_categories">Enable Categories</label>
                                    </div>
                                    <div class="setting-description">Organize posts by categories</div>
                                </div>
                                
                                <div class="setting-group">
                                    <label class="setting-label">Spam Threshold</label>
                                    <input type="number" name="spam_threshold" class="setting-input" 
                                           value="<?php echo $settings['spam_threshold']; ?>" min="1" max="50">
                                    <div class="setting-description">Number of reports before automatic action</div>
                                </div>
                            </div>
                        </div>

                        <div class="settings-section">
                            <h3><i class="fas fa-tachometer-alt"></i> Rate Limiting</h3>
                            <div class="setting-grid">
                                <div class="setting-group">
                                    <label class="setting-label">Posts per Hour</label>
                                    <input type="number" name="rate_limit_posts" class="setting-input" 
                                           value="<?php echo $settings['rate_limit_posts']; ?>" min="1" max="100">
                                    <div class="setting-description">Maximum posts a user can create per hour</div>
                                </div>
                                
                                <div class="setting-group">
                                    <label class="setting-label">Comments per Hour</label>
                                    <input type="number" name="rate_limit_comments" class="setting-input" 
                                           value="<?php echo $settings['rate_limit_comments']; ?>" min="1" max="200">
                                    <div class="setting-description">Maximum comments a user can create per hour</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Settings Tab -->
                    <div class="tab-content" id="advanced-tab">
                        <div class="settings-section">
                            <h3><i class="fas fa-code"></i> Custom Code</h3>
                            <div class="setting-group">
                                <label class="setting-label">Custom CSS</label>
                                <textarea name="custom_css" class="setting-input" 
                                          placeholder="Add custom CSS styles here..."><?php echo htmlspecialchars($settings['custom_css']); ?></textarea>
                                <div class="setting-description">Custom CSS that will be applied to all pages</div>
                            </div>
                            
                            <div class="setting-group">
                                <label class="setting-label">Custom JavaScript</label>
                                <textarea name="custom_js" class="setting-input" 
                                          placeholder="Add custom JavaScript here..."><?php echo htmlspecialchars($settings['custom_js']); ?></textarea>
                                <div class="setting-description">Custom JavaScript that will run on all pages</div>
                            </div>
                        </div>
                    </div>

                    <!-- Backup & Maintenance Tab -->
                    <div class="tab-content" id="backup-tab">
                        <div class="settings-section">
                            <h3><i class="fas fa-database"></i> Backup Settings</h3>
                            <div class="setting-grid">
                                <div class="setting-group">
                                    <label class="setting-label">Backup Frequency</label>
                                    <select name="backup_frequency" class="setting-input">
                                        <option value="daily" <?php echo $settings['backup_frequency'] === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                        <option value="weekly" <?php echo $settings['backup_frequency'] === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                        <option value="monthly" <?php echo $settings['backup_frequency'] === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    </select>
                                </div>
                                
                                <div class="setting-group">
                                    <label class="setting-label">Auto-prune Deleted Content (days)</label>
                                    <input type="number" name="auto_prune_deleted_content" class="setting-input" 
                                           value="<?php echo $settings['auto_prune_deleted_content']; ?>" min="1" max="365">
                                    <div class="setting-description">Automatically remove deleted content after this many days</div>
                                </div>
                            </div>
                        </div>

                        <div class="settings-section">
                            <h3><i class="fas fa-server"></i> System Information</h3>
                            <div class="backup-section">
                                <h4>Current System Status</h4>
                                <div class="backup-info">
                                    <div class="backup-item">
                                        <div class="label">PHP Version</div>
                                        <div class="value"><?php echo PHP_VERSION; ?></div>
                                    </div>
                                    <div class="backup-item">
                                        <div class="label">Database</div>
                                        <div class="value">MySQL</div>
                                    </div>
                                    <div class="backup-item">
                                        <div class="label">Storage Used</div>
                                        <div class="value"><?php 
                                            $size = 0;
                                            foreach(glob('../{,.}*', GLOB_BRACE) as $file) {
                                                if(is_file($file)) $size += filesize($file);
                                            }
                                            echo round($size / 1024 / 1024, 2) . ' MB';
                                        ?></div>
                                    </div>
                                    <div class="backup-item">
                                        <div class="label">Last Backup</div>
                                        <div class="value">Not available</div>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 1rem;">
                                    <button type="button" class="btn btn-primary" onclick="performBackup()">
                                        <i class="fas fa-download"></i> Create Backup Now
                                    </button>
                                    <button type="button" class="btn btn-secondary" style="margin-left: 0.5rem;" onclick="clearCache()">
                                        <i class="fas fa-broom"></i> Clear Cache
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="settings-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                        <button type="button" class="btn reset-button" onclick="resetSettings()">
                            <i class="fas fa-undo"></i> Reset to Defaults
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Tab switching functionality
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function() {
                const tabName = this.dataset.tab;
                
                // Update active tab button
                document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Update active tab content
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                document.getElementById(tabName + '-tab').classList.add('active');
            });
        });

        // Reset settings confirmation
        function resetSettings() {
            if (confirm('Are you sure you want to reset all settings to their default values?')) {
                // Could implement actual reset functionality here
                alert('Settings reset functionality would go here');
            }
        }

        // Backup functionality
        function performBackup() {
            if (confirm('Create a backup of the current database?')) {
                window.location = 'backup.php';
            }
        }

        // Clear cache functionality
        function clearCache() {
            if (confirm('Clear system cache?')) {
                // Would implement cache clearing logic here
                alert('Cache clearing functionality would go here');
            }
        }

        // Auto-hide notifications
        setTimeout(() => {
            document.querySelectorAll('.notification-banner').forEach(notification => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 300);
            });
        }, 5000);
    </script>
</body>
</html>