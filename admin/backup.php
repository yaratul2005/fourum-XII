<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Check admin access
if (!is_logged_in() || get_user_data(get_current_user_id())['username'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$message_type = '';

// Handle backup creation
if (isset($_POST['create_backup'])) {
    try {
        // Generate backup filename
        $timestamp = date('Y-m-d-H-i-s');
        $filename = "furom-backup-$timestamp.sql";
        $filepath = __DIR__ . "/backups/$filename";
        
        // Create backups directory if it doesn't exist
        if (!file_exists(__DIR__ . '/backups')) {
            mkdir(__DIR__ . '/backups', 0755, true);
        }
        
        // Get list of tables
        $tables = [];
        $result = $pdo->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        // Create backup content
        $backup_content = "-- Furom Database Backup\n";
        $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $backup_content .= "-- Tables: " . implode(', ', $tables) . "\n\n";
        
        foreach ($tables as $table) {
            // Drop table statement
            $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
            
            // Create table statement
            $create_result = $pdo->query("SHOW CREATE TABLE `$table`");
            $create_row = $create_result->fetch(PDO::FETCH_NUM);
            $backup_content .= $create_row[1] . ";\n\n";
            
            // Insert data
            $data_result = $pdo->query("SELECT * FROM `$table`");
            if ($data_result) {
                while ($row = $data_result->fetch(PDO::FETCH_ASSOC)) {
                    $values = array_map(function($value) use ($pdo) {
                        return $value === null ? 'NULL' : $pdo->quote($value);
                    }, $row);
                    
                    $backup_content .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                }
            }
            $backup_content .= "\n";
        }
        
        // Save backup file
        file_put_contents($filepath, $backup_content);
        
        $message = "Backup created successfully: $filename";
        $message_type = "success";
        
    } catch (Exception $e) {
        $message = "Error creating backup: " . $e->getMessage();
        $message_type = "error";
    }
}

// Handle backup download
if (isset($_GET['download'])) {
    $filename = basename($_GET['download']);
    $filepath = __DIR__ . "/backups/$filename";
    
    if (file_exists($filepath) && strpos($filename, 'furom-backup-') === 0) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        $message = "Backup file not found";
        $message_type = "error";
    }
}

// Handle backup deletion
if (isset($_POST['delete_backup'])) {
    $filename = basename($_POST['delete_backup']);
    $filepath = __DIR__ . "/backups/$filename";
    
    if (file_exists($filepath) && strpos($filename, 'furom-backup-') === 0) {
        unlink($filepath);
        $message = "Backup deleted successfully";
        $message_type = "success";
    } else {
        $message = "Backup file not found";
        $message_type = "error";
    }
}

// Get list of existing backups
$backups = [];
$backups_dir = __DIR__ . '/backups';
if (file_exists($backups_dir)) {
    $files = scandir($backups_dir);
    foreach ($files as $file) {
        if (strpos($file, 'furom-backup-') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $filepath = $backups_dir . '/' . $file;
            $backups[] = [
                'filename' => $file,
                'size' => filesize($filepath),
                'date' => date('M j, Y g:i A', filemtime($filepath))
            ];
        }
    }
    // Sort by date (newest first)
    usort($backups, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - <?php echo SITE_NAME; ?> Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .backup-container {
            padding: 1.5rem;
        }
        
        .backup-section {
            background: var(--admin-card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--admin-border);
        }
        
        .backup-section h3 {
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--admin-border);
            color: var(--admin-text-primary);
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .backup-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-card {
            background: var(--admin-darker);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid var(--admin-border);
        }
        
        .info-card .label {
            font-size: 0.9rem;
            color: var(--admin-text-secondary);
            margin-bottom: 0.25rem;
        }
        
        .info-card .value {
            font-weight: 500;
            color: var(--admin-text-primary);
            font-size: 1.1rem;
        }
        
        .backup-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .btn-large {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .backups-list {
            margin-top: 1.5rem;
        }
        
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: var(--admin-darker);
            border-radius: 8px;
            margin-bottom: 0.75rem;
            border: 1px solid var(--admin-border);
            transition: var(--admin-transition);
        }
        
        .backup-item:hover {
            border-color: var(--admin-primary);
            transform: translateY(-2px);
        }
        
        .backup-details {
            flex: 1;
        }
        
        .backup-name {
            font-weight: 500;
            color: var(--admin-text-primary);
            margin-bottom: 0.25rem;
        }
        
        .backup-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: var(--admin-text-secondary);
        }
        
        .backup-actions-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn-small {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--admin-transition);
        }
        
        .action-btn-small.download {
            background: var(--admin-primary);
            color: white;
        }
        
        .action-btn-small.download:hover {
            background: var(--admin-primary-hover);
        }
        
        .action-btn-small.delete {
            background: var(--admin-danger);
            color: white;
        }
        
        .action-btn-small.delete:hover {
            background: #dc2626;
        }
        
        .empty-backups {
            text-align: center;
            padding: 3rem;
            color: var(--admin-text-muted);
        }
        
        .empty-backups i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .upload-section {
            background: var(--admin-darker);
            border-radius: 8px;
            padding: 1.5rem;
            border: 2px dashed var(--admin-border);
            text-align: center;
            margin-top: 1rem;
            transition: var(--admin-transition);
        }
        
        .upload-section:hover {
            border-color: var(--admin-primary);
        }
        
        .upload-section.drag-over {
            border-color: var(--admin-primary);
            background: rgba(59, 130, 246, 0.1);
        }
        
        .file-input {
            display: none;
        }
        
        .upload-label {
            display: block;
            padding: 2rem;
            cursor: pointer;
            color: var(--admin-text-secondary);
        }
        
        .upload-label:hover {
            color: var(--admin-text-primary);
        }
        
        .upload-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: block;
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
                <a href="backup.php" class="nav-item active">
                    <i class="fas fa-database"></i>
                    <span>Backup & Restore</span>
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
                    <h1><i class="fas fa-database"></i> Backup & Restore</h1>
                    <div class="admin-info">
                        <span>Manage database backups</span>
                        <a href="../logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </header>

            <div class="backup-container">
                <?php if ($message): ?>
                    <div class="notification <?php echo $message_type; ?> show">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- System Information -->
                <div class="backup-section">
                    <h3><i class="fas fa-info-circle"></i> System Information</h3>
                    <div class="backup-info">
                        <div class="info-card">
                            <div class="label">Database</div>
                            <div class="value">MySQL</div>
                        </div>
                        <div class="info-card">
                            <div class="label">Tables</div>
                            <div class="value">
                                <?php 
                                $table_count = $pdo->query("SHOW TABLES")->rowCount();
                                echo $table_count;
                                ?>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="label">Storage Used</div>
                            <div class="value">
                                <?php 
                                $size = 0;
                                foreach(glob('../{,.}*', GLOB_BRACE) as $file) {
                                    if(is_file($file)) $size += filesize($file);
                                }
                                echo round($size / 1024 / 1024, 2) . ' MB';
                                ?>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="label">Existing Backups</div>
                            <div class="value"><?php echo count($backups); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Backup Actions -->
                <div class="backup-section">
                    <h3><i class="fas fa-download"></i> Create Backup</h3>
                    <p style="color: var(--admin-text-secondary); margin-bottom: 1.5rem;">
                        Create a complete backup of your database including all tables and data.
                    </p>
                    
                    <form method="POST">
                        <div class="backup-actions">
                            <button type="submit" name="create_backup" class="btn btn-primary btn-large">
                                <i class="fas fa-database"></i> Create Database Backup
                            </button>
                        </div>
                    </form>
                    
                    <div style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                        <strong style="color: var(--admin-warning);"><i class="fas fa-exclamation-triangle"></i> Important:</strong>
                        <span style="color: var(--admin-text-secondary);">Backups include all user data, posts, and settings. Store backups securely and test restoration regularly.</span>
                    </div>
                </div>

                <!-- Existing Backups -->
                <div class="backup-section">
                    <h3><i class="fas fa-history"></i> Existing Backups</h3>
                    
                    <?php if (empty($backups)): ?>
                        <div class="empty-backups">
                            <i class="fas fa-archive"></i>
                            <h3>No backups found</h3>
                            <p>Create your first backup using the button above.</p>
                        </div>
                    <?php else: ?>
                        <div class="backups-list">
                            <?php foreach($backups as $backup): ?>
                                <div class="backup-item">
                                    <div class="backup-details">
                                        <div class="backup-name"><?php echo htmlspecialchars($backup['filename']); ?></div>
                                        <div class="backup-meta">
                                            <span><i class="fas fa-calendar"></i> <?php echo $backup['date']; ?></span>
                                            <span><i class="fas fa-weight-hanging"></i> <?php echo round($backup['size'] / 1024, 1); ?> KB</span>
                                        </div>
                                    </div>
                                    <div class="backup-actions-group">
                                        <a href="?download=<?php echo urlencode($backup['filename']); ?>" 
                                           class="action-btn-small download">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="delete_backup" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                            <button type="submit" class="action-btn-small delete" 
                                                    onclick="return confirm('Delete this backup? This cannot be undone.')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Restore Section -->
                <div class="backup-section">
                    <h3><i class="fas fa-upload"></i> Restore Backup</h3>
                    <p style="color: var(--admin-text-secondary); margin-bottom: 1.5rem;">
                        Upload and restore a previously created backup file. <strong style="color: var(--admin-danger);">Warning: This will overwrite all current data!</strong>
                    </p>
                    
                    <div class="upload-section" id="dropZone">
                        <form method="POST" enctype="multipart/form-data">
                            <label class="upload-label" for="backupFile">
                                <span class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></span>
                                <div>Drag & drop backup file here or click to browse</div>
                                <div style="font-size: 0.9rem; margin-top: 0.5rem;">Supports .sql files only</div>
                                <input type="file" id="backupFile" name="backup_file" class="file-input" accept=".sql">
                            </label>
                            
                            <div style="margin-top: 1rem;">
                                <button type="submit" name="restore_backup" class="btn btn-warning" id="restoreBtn" disabled>
                                    <i class="fas fa-upload"></i> Restore Backup
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                        <strong style="color: var(--admin-danger);"><i class="fas fa-exclamation-triangle"></i> Critical Warning:</strong>
                        <span style="color: var(--admin-text-secondary);">Restoring a backup will permanently delete all current data and replace it with the backup data. Always create a backup before restoring.</span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Drag and drop functionality
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('backupFile');
        const restoreBtn = document.getElementById('restoreBtn');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            dropZone.classList.add('drag-over');
        }

        function unhighlight() {
            dropZone.classList.remove('drag-over');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        fileInput.addEventListener('change', function() {
            handleFiles(this.files);
        });

        function handleFiles(files) {
            if (files.length > 0) {
                const file = files[0];
                if (file.name.endsWith('.sql')) {
                    // Update the file input
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    fileInput.files = dataTransfer.files;
                    
                    // Enable restore button
                    restoreBtn.disabled = false;
                    restoreBtn.innerHTML = '<i class="fas fa-upload"></i> Restore ' + file.name;
                    
                    // Show file info
                    dropZone.querySelector('.upload-label div:first-child').innerHTML = 
                        '<i class="fas fa-file-alt"></i> Selected: ' + file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';
                } else {
                    alert('Please select a .sql file');
                }
            }
        }

        // Restore confirmation
        document.querySelector('form[method="POST"][enctype]').addEventListener('submit', function(e) {
            if (this.querySelector('[name="restore_backup"]')) {
                if (!confirm('WARNING: This will permanently delete all current data and restore from the backup. Are you absolutely sure?')) {
                    e.preventDefault();
                }
            }
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