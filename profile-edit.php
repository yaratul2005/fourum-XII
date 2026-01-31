<?php
// Enhanced Profile Editor with Improved Image Upload and Validation
// Define NO_CACHE to prevent cache headers on this page
define('NO_CACHE', true);

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

try {
    require_once 'config.php';
    require_once 'includes/functions.php';
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!is_logged_in()) {
        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
    
    $user_id = get_current_user_id();
    $user_data = get_user_data($user_id);
    
    if (!$user_data) {
        header('Location: index.php');
        exit();
    }
    
    $message = '';
    $message_type = '';
    $upload_errors = [];
    
    // Handle profile updates
    if (isset($_POST['update_profile'])) {
        try {
            $updates = [];
            $params = [];
            
            // Handle avatar upload with better error handling
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $upload_result = handle_avatar_upload_improved($_FILES['avatar'], $user_id);
                if ($upload_result['success']) {
                    $updates[] = "avatar = ?";
                    $params[] = $upload_result['filename'];
                    $message .= 'Avatar updated successfully. ';
                    $message_type = 'success';
                } else {
                    $upload_errors[] = $upload_result['error'];
                }
            } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                // Handle upload errors
                $upload_errors[] = get_upload_error_message($_FILES['avatar']['error']);
            }
            
            // Handle bio update
            if (isset($_POST['bio'])) {
                $bio = trim($_POST['bio']);
                if (strlen($bio) > 500) {
                    throw new Exception('Bio must be 500 characters or less');
                }
                $updates[] = "bio = ?";
                $params[] = $bio;
            }
            
            // Handle website update
            if (isset($_POST['website'])) {
                $website = trim($_POST['website']);
                if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
                    throw new Exception('Invalid website URL format');
                }
                $updates[] = "website = ?";
                $params[] = $website;
            }
            
            // Handle location update
            if (isset($_POST['location'])) {
                $location = trim($_POST['location']);
                if (strlen($location) > 100) {
                    throw new Exception('Location must be 100 characters or less');
                }
                $updates[] = "location = ?";
                $params[] = $location;
            }
            
            // Handle signature update
            if (isset($_POST['signature'])) {
                $signature = trim($_POST['signature']);
                if (strlen($signature) > 300) {
                    throw new Exception('Signature must be 300 characters or less');
                }
                $updates[] = "signature = ?";
                $params[] = $signature;
            }
            
            // Update username with better validation
            if (isset($_POST['username']) && $_POST['username'] !== $user_data['username']) {
                $new_username = sanitize_input($_POST['username']);
                if (strlen($new_username) < 3 || strlen($new_username) > 30) {
                    throw new Exception('Username must be between 3 and 30 characters');
                }
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
                    throw new Exception('Username can only contain letters, numbers, and underscores');
                }
                
                // Check uniqueness
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$new_username, $user_id]);
                if ($stmt->fetch()) {
                    throw new Exception('Username already taken');
                }
                
                $updates[] = "username = ?";
                $params[] = $new_username;
                $message .= 'Username updated successfully. ';
                $message_type = 'success';
                
                // Update session username
                $_SESSION['username'] = $new_username;
            }
            
            if (!empty($updates)) {
                $params[] = $user_id;
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute($params)) {
                    if (empty($message)) {
                        $message = 'Profile updated successfully!';
                        $message_type = 'success';
                    }
                } else {
                    throw new Exception('Failed to update profile');
                }
            } else if (empty($message) && empty($upload_errors)) {
                $message = 'No changes made to profile.';
                $message_type = 'info';
            }
            
            // Handle upload errors
            if (!empty($upload_errors)) {
                $message .= 'Upload errors: ' . implode(', ', $upload_errors);
                $message_type = $message_type === 'success' ? 'warning' : 'error';
            }
            
            // Refresh user data
            $user_data = get_user_data($user_id);
            
        } catch (Exception $e) {
            $message = 'Error updating profile: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        try {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validate passwords
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception('All password fields are required');
            }
            
            if (strlen($new_password) < 8) {
                throw new Exception('New password must be at least 8 characters long');
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception('New passwords do not match');
            }
            
            // Verify current password
            if (!password_verify($current_password, $user_data['password'])) {
                throw new Exception('Current password is incorrect');
            }
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $user_id])) {
                $message = 'Password changed successfully!';
                $message_type = 'success';
            } else {
                throw new Exception('Failed to change password');
            }
            
        } catch (Exception $e) {
            $message = 'Error changing password: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
} catch (Exception $e) {
    $message = 'System error: ' . $e->getMessage();
    $message_type = 'error';
}

// Don't flush output buffer yet - let header handle cache settings first
// ob_end_flush(); // Moved to after HTML output
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - <?php echo htmlspecialchars($user_data['username']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-edit-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: var(--card-bg);
            border-radius: 15px;
            border: 1px solid var(--border-color);
        }
        
        .avatar-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: var(--card-bg);
            border-radius: 15px;
            border: 1px solid var(--border-color);
        }
        
        .avatar-preview-container {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .avatar-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .avatar-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px var(--primary);
        }
        
        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            color: white;
            font-size: 0.9rem;
            text-align: center;
        }
        
        .avatar-preview-container:hover .avatar-overlay {
            opacity: 1;
        }
        
        .form-section {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .section-title {
            margin-top: 0;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--primary);
            font-family: 'Orbitron', monospace;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .upload-feedback {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .upload-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
        }
        
        .upload-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        
        .character-count {
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-align: right;
            margin-top: 5px;
        }
        
        .character-limit-exceeded {
            color: var(--danger);
        }
    </style>
    
    /* Loading spinner styles */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .loading-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    
    .spinner {
        width: 50px;
        height: 50px;
        border: 3px solid rgba(0, 245, 255, 0.3);
        border-radius: 50%;
        border-top-color: var(--primary);
        animation: spin 1s ease-in-out infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-container">
        <div class="container">
            <div class="profile-edit-container">
                <div class="profile-header">
                    <h1><i class="fas fa-user-edit"></i> Edit Your Profile</h1>
                    <p>Customize your profile and manage your account settings</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="profileForm">
                    <!-- Avatar Section -->
                    <div class="form-section">
                        <h2 class="section-title"><i class="fas fa-camera"></i> Profile Picture</h2>
                        <div class="avatar-section">
                            <div class="avatar-preview-container">
                                <img src="<?php echo !empty($user_data['avatar']) ? htmlspecialchars($user_data['avatar']) : 'assets/images/default-avatar.png'; ?>" 
                                     alt="Profile Picture" class="avatar-preview" id="avatarPreview">
                                <div class="avatar-overlay">
                                    <div>
                                        <i class="fas fa-camera"></i><br>
                                        Click to change
                                    </div>
                                </div>
                            </div>
                            <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display: none;">
                            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 10px;">
                                <i class="fas fa-info-circle"></i> JPG, PNG, or GIF files only. Max 2MB.
                            </p>
                            <div id="uploadFeedback"></div>
                        </div>
                    </div>
                    
                    <!-- Basic Information -->
                    <div class="form-section">
                        <h2 class="section-title"><i class="fas fa-user"></i> Basic Information</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user_data['username']); ?>"
                                       class="form-control" minlength="3" maxlength="30" required>
                                <small>3-30 characters, letters, numbers, and underscores only</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user_data['email']); ?>"
                                       class="form-control" disabled>
                                <small>Email cannot be changed for security reasons</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio" class="form-control" 
                                      maxlength="500" rows="4"><?php echo htmlspecialchars($user_data['bio'] ?? ''); ?></textarea>
                            <div class="character-count" id="bioCount">0/500 characters</div>
                        </div>
                    </div>
                    
                    <!-- Additional Information -->
                    <div class="form-section">
                        <h2 class="section-title"><i class="fas fa-info-circle"></i> Additional Information</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="location">Location</label>
                                <input type="text" id="location" name="location" 
                                       value="<?php echo htmlspecialchars($user_data['location'] ?? ''); ?>"
                                       class="form-control" maxlength="100">
                                <small>Where are you based?</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="website">Website</label>
                                <input type="url" id="website" name="website" 
                                       value="<?php echo htmlspecialchars($user_data['website'] ?? ''); ?>"
                                       class="form-control" placeholder="https://yourwebsite.com">
                                <small>Your personal or professional website</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="signature">Forum Signature</label>
                            <textarea id="signature" name="signature" class="form-control" 
                                      maxlength="300" rows="3"><?php echo htmlspecialchars($user_data['signature'] ?? ''); ?></textarea>
                            <div class="character-count" id="signatureCount">0/300 characters</div>
                            <small>Displayed at the end of your posts</small>
                        </div>
                    </div>
                    
                    <div class="form-group" style="text-align: center;">
                        <button type="submit" name="update_profile" class="btn btn-primary" style="padding: 12px 30px;">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="profile.php?id=<?php echo $user_id; ?>" class="btn btn-secondary" style="padding: 12px 30px; margin-left: 15px;">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
                
                <!-- Password Change Section -->
                <div class="form-section">
                    <h2 class="section-title"><i class="fas fa-key"></i> Change Password</h2>
                    <form method="POST" id="passwordForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" 
                                       class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" 
                                       class="form-control" minlength="8" required>
                                <small>At least 8 characters long</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       class="form-control" minlength="8" required>
                            </div>
                        </div>
                        
                        <div class="form-group" style="text-align: center; margin-top: 20px;">
                            <button type="submit" name="change_password" class="btn btn-warning">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Show loading overlay
        function showLoading() {
            document.getElementById('loadingOverlay').classList.add('active');
        }
        
        // Hide loading overlay
        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('active');
        }
        
        // Avatar upload handling
        document.getElementById('avatarPreview').addEventListener('click', function() {
            document.getElementById('avatarInput').click();
        });
        
        // Hide loading on page load
        window.addEventListener('load', function() {
            hideLoading();
        });

        document.getElementById('avatarInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const feedback = document.getElementById('uploadFeedback');
            
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    feedback.innerHTML = '<div class="upload-feedback upload-error"><i class="fas fa-exclamation-circle"></i> Invalid file type. Please select a JPG, PNG, or GIF image.</div>';
                    return;
                }
                
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    feedback.innerHTML = '<div class="upload-feedback upload-error"><i class="fas fa-exclamation-circle"></i> File too large. Maximum size is 2MB.</div>';
                    return;
                }
                
                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                    feedback.innerHTML = '<div class="upload-feedback upload-success"><i class="fas fa-check-circle"></i> Image selected. Click "Save Changes" to upload.</div>';
                };
                reader.readAsDataURL(file);
            }
        });

        // Character counters
        function updateCharacterCount(textareaId, counterId, maxLength) {
            const textarea = document.getElementById(textareaId);
            const counter = document.getElementById(counterId);
            
            function updateCount() {
                const currentLength = textarea.value.length;
                counter.textContent = `${currentLength}/${maxLength} characters`;
                counter.className = currentLength > maxLength * 0.9 ? 'character-count character-limit-exceeded' : 'character-count';
            }
            
            textarea.addEventListener('input', updateCount);
            updateCount(); // Initial count
        }

        // Initialize character counters
        updateCharacterCount('bio', 'bioCount', 500);
        updateCharacterCount('signature', 'signatureCount', 300);

        // Form validation and loading
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            showLoading();
            
            const username = document.getElementById('username').value;
            const bio = document.getElementById('bio').value;
            const location = document.getElementById('location').value;
            const signature = document.getElementById('signature').value;
            
            // Username validation
            if (username.length < 3 || username.length > 30) {
                e.preventDefault();
                hideLoading();
                alert('Username must be between 3 and 30 characters');
                return false;
            }
            
            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                e.preventDefault();
                alert('Username can only contain letters, numbers, and underscores');
                return false;
            }
            
            // Length validations
            if (bio.length > 500) {
                e.preventDefault();
                alert('Bio must be 500 characters or less');
                return false;
            }
            
            if (location.length > 100) {
                e.preventDefault();
                alert('Location must be 100 characters or less');
                return false;
            }
            
            if (signature.length > 300) {
                e.preventDefault();
                alert('Signature must be 300 characters or less');
                return false;
            }
        });

        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            showLoading();
            
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword.length < 8) {
                e.preventDefault();
                hideLoading();
                alert('New password must be at least 8 characters long');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match');
                return false;
            }
            
            if (currentPassword === newPassword) {
                e.preventDefault();
                alert('New password must be different from current password');
                return false;
            }
        });
    </script>
</body>
</html>

<?php
// Flush output buffer after HTML is complete
ob_end_flush();

function handle_avatar_upload_improved($file, $user_id) {
    global $pdo;
    
    try {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        // Validate file
        if (!in_array($file['type'], $allowed_types)) {
            return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'];
        }
        
        if ($file['size'] > $max_size) {
            return ['success' => false, 'error' => 'File too large. Maximum size is 2MB.'];
        }
        
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/avatars/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                return ['success' => false, 'error' => 'Failed to create upload directory'];
            }
        }
        
        // Check if directory is writable
        if (!is_writable($upload_dir)) {
            return ['success' => false, 'error' => 'Upload directory is not writable'];
        }
        
        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Remove old avatar if exists
            try {
                $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $old_avatar = $stmt->fetchColumn();
                
                if ($old_avatar && file_exists($old_avatar) && strpos($old_avatar, 'avatar_') !== false) {
                    unlink($old_avatar);
                }
            } catch (Exception $e) {
                // Log error but don't fail the upload
                error_log("Failed to remove old avatar: " . $e->getMessage());
            }
            
            return ['success' => true, 'filename' => $filepath];
        } else {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }
        
    } catch (Exception $e) {
        error_log("Avatar upload error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Upload failed: ' . $e->getMessage()];
    }
}

function get_upload_error_message($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'File exceeds maximum allowed size';
        case UPLOAD_ERR_PARTIAL:
            return 'File was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension';
        default:
            return 'Unknown upload error';
    }
}
?>