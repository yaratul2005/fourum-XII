<?php
// Enhanced Profile Editor with Image Upload
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
    
    // Handle profile updates
    if (isset($_POST['update_profile'])) {
        try {
            $updates = [];
            $params = [];
            
            // Handle avatar upload
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $upload_result = handle_avatar_upload($_FILES['avatar'], $user_id);
                if ($upload_result['success']) {
                    $updates[] = "avatar = ?";
                    $params[] = $upload_result['filename'];
                    $message .= 'Avatar updated successfully. ';
                    $message_type = 'success';
                } else {
                    $message .= 'Avatar upload failed: ' . $upload_result['error'] . ' ';
                    $message_type = 'error';
                }
            }
            
            // Handle other profile fields
            $allowed_fields = ['bio', 'website', 'location', 'signature'];
            foreach ($allowed_fields as $field) {
                if (isset($_POST[$field])) {
                    $value = trim($_POST[$field]);
                    if ($field === 'website' && !empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                        throw new Exception('Invalid website URL');
                    }
                    $updates[] = "$field = ?";
                    $params[] = $value;
                }
            }
            
            // Update username (with uniqueness check)
            if (isset($_POST['username']) && $_POST['username'] !== $user_data['username']) {
                $new_username = sanitize_input($_POST['username']);
                if (strlen($new_username) < 3 || strlen($new_username) > 30) {
                    throw new Exception('Username must be between 3 and 30 characters');
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
            }
            
            if (!empty($updates)) {
                $params[] = $user_id;
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                if (empty($message)) {
                    $message = 'Profile updated successfully!';
                    $message_type = 'success';
                }
            } else if (empty($message)) {
                $message = 'No changes made to profile.';
                $message_type = 'info';
            }
            
            // Refresh user data
            $user_data = get_user_data($user_id);
            
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        try {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Verify current password
            if (!password_verify($current_password, $user_data['password'])) {
                throw new Exception('Current password is incorrect');
            }
            
            // Validate new password
            if (strlen($new_password) < 8) {
                throw new Exception('New password must be at least 8 characters long');
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception('New passwords do not match');
            }
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            $message = 'Password changed successfully!';
            $message_type = 'success';
            
        } catch (Exception $e) {
            $message = 'Password change error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
} catch (Exception $e) {
    $fatal_error = $e->getMessage();
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - <?php echo htmlspecialchars($user_data['username'] ?? 'User'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Cyberpunk Theme Integration */
        :root {
            --cyber-primary: #00f5ff;
            --cyber-secondary: #ff00ff;
            --cyber-accent: #ff6b6b;
            --cyber-dark: #0a0a1a;
            --cyber-darker: #050510;
            --cyber-card: #121225;
            --cyber-text: #ffffff;
            --cyber-text-secondary: #a0a0c0;
            --cyber-border: #2a2a4a;
            --cyber-success: #00ff9d;
            --cyber-warning: #ffcc00;
            --cyber-danger: #ff4757;
            --cyber-neon: 0 0 10px var(--cyber-primary), 0 0 20px var(--cyber-primary), 0 0 30px var(--cyber-primary);
        }
        
        body {
            font-family: 'Exo 2', sans-serif;
            background: var(--cyber-dark);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(0, 245, 255, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(255, 0, 255, 0.1) 0%, transparent 20%);
            color: var(--cyber-text);
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }
        
        .profile-edit-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            animation: fadeInDown 0.8s ease-out;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .profile-header h1 {
            font-family: 'Orbitron', monospace;
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--cyber-primary), var(--cyber-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: var(--cyber-neon);
            margin-bottom: 0.5rem;
        }
        
        .profile-header p {
            color: var(--cyber-text-secondary);
            font-size: 1.1rem;
        }
        
        .avatar-upload {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .avatar-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--cyber-primary);
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 0 20px rgba(0, 245, 255, 0.3);
        }
        
        .avatar-preview:hover {
            transform: scale(1.1);
            border-color: var(--cyber-accent);
            box-shadow: 0 0 30px rgba(255, 107, 107, 0.5);
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
            font-size: 1.5rem;
        }
        
        .avatar-upload:hover .avatar-overlay {
            opacity: 1;
        }
        
        .form-section {
            background: var(--cyber-card);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--cyber-border);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }
        
        .form-section:nth-child(2) { animation-delay: 0.1s; }
        .form-section:nth-child(3) { animation-delay: 0.2s; }
        .form-section:nth-child(4) { animation-delay: 0.3s; }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--cyber-primary), var(--cyber-secondary));
            box-shadow: var(--cyber-neon);
        }
        
        .form-section h2 {
            font-family: 'Orbitron', monospace;
            color: var(--cyber-text);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.4rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--cyber-text);
            font-weight: 600;
            font-size: 1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid var(--cyber-border);
            background: rgba(0, 0, 0, 0.3);
            color: var(--cyber-text);
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Exo 2', sans-serif;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--cyber-primary);
            box-shadow: 0 0 20px rgba(0, 245, 255, 0.3);
            background: rgba(0, 0, 0, 0.5);
            transform: translateY(-2px);
        }
        
        .form-control::placeholder {
            color: var(--cyber-text-secondary);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 50px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 10px;
            position: relative;
            overflow: hidden;
            font-family: 'Exo 2', sans-serif;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--cyber-accent), var(--cyber-secondary));
            color: white;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.6);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--cyber-success);
            border: 2px solid var(--cyber-success);
        }
        
        .btn-secondary:hover {
            background: var(--cyber-success);
            color: var(--cyber-dark);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 255, 157, 0.4);
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .message.success { 
            background: rgba(0, 255, 157, 0.15); 
            color: var(--cyber-success); 
            border: 1px solid rgba(0, 255, 157, 0.3);
            box-shadow: 0 0 15px rgba(0, 255, 157, 0.2);
        }
        
        .message.error { 
            background: rgba(255, 71, 87, 0.15); 
            color: var(--cyber-danger); 
            border: 1px solid rgba(255, 71, 87, 0.3);
            box-shadow: 0 0 15px rgba(255, 71, 87, 0.2);
        }
        
        .message.info { 
            background: rgba(0, 245, 255, 0.15); 
            color: var(--cyber-primary); 
            border: 1px solid rgba(0, 245, 255, 0.3);
            box-shadow: 0 0 15px rgba(0, 245, 255, 0.2);
        }
        
        .password-section {
            border-top: 1px solid var(--cyber-border);
            margin-top: 2rem;
            padding-top: 2rem;
        }
        
        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 10px;
            margin-top: 10px;
            display: none;
            border: 2px solid var(--cyber-border);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .file-input {
            display: none;
        }
        
        .upload-instructions {
            text-align: center;
            color: var(--cyber-text-secondary);
            font-size: 0.9rem;
            margin-top: 10px;
        }
        
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
        }
    </style>
</head>
<body>
    <!-- Particles Background -->
    <div id="particles-js"></div>
    
    <?php if (isset($fatal_error)): ?>
        <div class="error-banner">
            <h2>🚨 System Error</h2>
            <p><?php echo htmlspecialchars($fatal_error); ?></p>
            <p><a href="index.php">Return to main site</a></p>
        </div>
    <?php else: ?>
    
    <div class="profile-edit-container">
        <div class="profile-header">
            <h1>Edit Your Profile</h1>
            <p>Customize your public profile and account settings</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="profileForm">
            <!-- Avatar Section -->
            <div class="form-section">
                <h2><i class="fas fa-camera"></i> Profile Picture</h2>
                <div class="avatar-upload">
                    <img src="<?php echo !empty($user_data['avatar']) ? htmlspecialchars($user_data['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($user_data['username']) . '&background=4ecdc4&color=fff'; ?>" 
                         alt="Profile Picture" class="avatar-preview" id="avatarPreview">
                    <div class="avatar-overlay">
                        <i class="fas fa-camera"></i>
                    </div>
                    <input type="file" name="avatar" id="avatarInput" class="file-input" accept="image/*">
                </div>
                <div class="upload-instructions">
                    <p>Click the image to upload a new avatar</p>
                    <p>Supported formats: JPG, PNG, GIF (Max 2MB)</p>
                </div>
                <img id="imagePreview" class="preview-image" alt="Preview">
            </div>
            
            <!-- Basic Info Section -->
            <div class="form-section">
                <h2><i class="fas fa-user"></i> Basic Information</h2>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($user_data['username']); ?>" 
                           minlength="3" maxlength="30" required>
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" class="form-control" 
                              placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user_data['bio'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" class="form-control" 
                           value="<?php echo htmlspecialchars($user_data['location'] ?? ''); ?>" 
                           placeholder="Where are you from?">
                </div>
                
                <div class="form-group">
                    <label for="website">Website</label>
                    <input type="url" id="website" name="website" class="form-control" 
                           value="<?php echo htmlspecialchars($user_data['website'] ?? ''); ?>" 
                           placeholder="https://yourwebsite.com">
                </div>
            </div>
            
            <!-- Signature Section -->
            <div class="form-section">
                <h2><i class="fas fa-signature"></i> Forum Signature</h2>
                <div class="form-group">
                    <label for="signature">Signature</label>
                    <textarea id="signature" name="signature" class="form-control" 
                              placeholder="This will appear at the bottom of your posts..."><?php echo htmlspecialchars($user_data['signature'] ?? ''); ?></textarea>
                    <small class="upload-instructions">Supports basic Markdown formatting</small>
                </div>
            </div>
            
            <!-- Submit Button -->
            <div style="text-align: center; margin: 2rem 0;">
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="profile.php?username=<?php echo urlencode($user_data['username']); ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
        
        <!-- Password Change Section -->
        <div class="form-section">
            <h2><i class="fas fa-lock"></i> Change Password</h2>
            <form method="POST" id="passwordForm">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" 
                           minlength="8" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                           minlength="8" required>
                </div>
                
                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
    </div>
    
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        // Initialize particles
        particlesJS('particles-js', {
            particles: {
                number: { value: 80, density: { enable: true, value_area: 800 } },
                color: { value: '#4ecdc4' },
                shape: { type: 'circle' },
                opacity: { value: 0.5, random: true },
                size: { value: 3, random: true },
                line_linked: { enable: true, distance: 150, color: '#4ecdc4', opacity: 0.4, width: 1 },
                move: { enable: true, speed: 2, direction: 'none', random: true, straight: false, out_mode: 'out' }
            }
        });

        // Avatar upload handling
        document.getElementById('avatarPreview').addEventListener('click', function() {
            document.getElementById('avatarInput').click();
        });

        document.getElementById('avatarInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value;
            if (username.length < 3 || username.length > 30) {
                e.preventDefault();
                alert('Username must be between 3 and 30 characters');
                return false;
            }
        });

        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                return false;
            }
        });
    </script>
</body>
</html>

<?php
function handle_avatar_upload($file, $user_id) {
    global $pdo;
    
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
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Remove old avatar if exists
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $old_avatar = $stmt->fetchColumn();
        
        if ($old_avatar && file_exists($old_avatar)) {
            unlink($old_avatar);
        }
        
        return ['success' => true, 'filename' => $filepath];
    } else {
        return ['success' => false, 'error' => 'Failed to upload file'];
    }
}
?>