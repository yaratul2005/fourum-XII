<?php
// KYC Verification Submission Page
require_once 'config.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

$current_user = get_user_data(get_current_user_id());

// Check if KYC is enabled
$kyc_enabled = get_setting('kyc_enabled', '1');
if ($kyc_enabled !== '1') {
    header('Location: index.php');
    exit();
}

// Check minimum EXP requirement
$kyc_min_level = (int)get_setting('kyc_min_level', '500');
if ($current_user['exp'] < $kyc_min_level) {
    $message = "You need at least {$kyc_min_level} EXP to submit KYC verification.";
    $message_type = 'error';
}

$errors = [];
$success = '';

// Handle KYC submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($message)) {
    try {
        // Validate required fields
        $required_fields = ['document_type', 'document_number', 'first_name', 'last_name', 'date_of_birth'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = "Please fill in the {$field} field.";
            }
        }
        
        // Check if user already has pending KYC
        $stmt = $pdo->prepare("SELECT id, status FROM kyc_documents WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$current_user['id']]);
        if ($stmt->fetch()) {
            $errors[] = 'You already have a pending KYC verification.';
        }
        
        // Handle file uploads
        $uploaded_files = [];
        $upload_dir = 'uploads/kyc/';
        
        // Create upload directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_fields = ['front_image', 'back_image', 'selfie_image'];
        foreach ($file_fields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$field];
                
                // Validate file type
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($file['type'], $allowed_types)) {
                    $errors[] = "Invalid file type for {$field}. Please upload JPG, PNG, or GIF files only.";
                    continue;
                }
                
                // Validate file size (max 5MB)
                if ($file['size'] > 5 * 1024 * 1024) {
                    $errors[] = "File {$field} is too large. Maximum size is 5MB.";
                    continue;
                }
                
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '_' . $field . '.' . $extension;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $uploaded_files[$field] = $filepath;
                } else {
                    $errors[] = "Failed to upload {$field}. Please try again.";
                }
            } elseif ($field === 'front_image') {
                $errors[] = 'Front image of document is required.';
            }
        }
        
        if (empty($errors)) {
            // Insert KYC record
            $stmt = $pdo->prepare("
                INSERT INTO kyc_documents 
                (user_id, document_type, document_number, first_name, last_name, date_of_birth, 
                 issue_date, expiry_date, front_image, back_image, selfie_image, status, submitted_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $result = $stmt->execute([
                $current_user['id'],
                $_POST['document_type'],
                $_POST['document_number'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['date_of_birth'],
                $_POST['issue_date'] ?? null,
                $_POST['expiry_date'] ?? null,
                $uploaded_files['front_image'],
                $uploaded_files['back_image'] ?? null,
                $uploaded_files['selfie_image'] ?? null
            ]);
            
            if ($result) {
                // Log the submission
                $kyc_id = $pdo->lastInsertId();
                log_kyc_action($kyc_id, 'submitted', $current_user['id']);
                
                // Update user KYC status
                $stmt = $pdo->prepare("UPDATE users SET kyc_status = 'pending' WHERE id = ?");
                $stmt->execute([$current_user['id']]);
                
                // Send notification to admins
                send_admin_notification('New KYC submission pending review', "User {$current_user['username']} has submitted KYC verification.");
                
                $success = 'KYC verification submitted successfully! Our team will review your documents within 24-48 hours.';
            } else {
                $errors[] = 'Failed to submit KYC verification. Please try again.';
            }
        }
        
    } catch (Exception $e) {
        error_log("KYC submission error: " . $e->getMessage());
        $errors[] = 'An error occurred while processing your submission. Please try again.';
    }
}

// Get user's current KYC status
$stmt = $pdo->prepare("SELECT * FROM kyc_documents WHERE user_id = ? ORDER BY submitted_at DESC LIMIT 1");
$stmt->execute([$current_user['id']]);
$current_kyc = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Verification - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .kyc-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .kyc-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .kyc-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .kyc-status {
            text-align: center;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .status-pending {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #f59e0b;
        }
        
        .status-approved {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
        }
        
        .status-rejected {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        
        .file-upload {
            border: 2px dashed var(--border-color);
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            margin: 1rem 0;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-upload:hover {
            border-color: var(--primary);
            background: rgba(0, 245, 255, 0.05);
        }
        
        .file-upload.dragover {
            border-color: var(--primary);
            background: rgba(0, 245, 255, 0.1);
        }
        
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 10px;
            display: none;
        }
        
        .requirements {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }
        
        .requirements h3 {
            color: var(--primary);
            margin-top: 0;
        }
        
        .requirements ul {
            color: var(--text-secondary);
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-container">
        <div class="container">
            <div class="kyc-container">
                <div class="kyc-header">
                    <h1><i class="fas fa-id-card"></i> KYC Verification</h1>
                    <p>Verify your identity to unlock advanced features and create categories</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert error">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert error">
                        <h4>Please fix the following errors:</h4>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($current_kyc): ?>
                    <div class="kyc-card">
                        <h2>Your Current KYC Status</h2>
                        <div class="kyc-status status-<?php echo $current_kyc['status']; ?>">
                            <h3>
                                <?php 
                                switch ($current_kyc['status']) {
                                    case 'pending': echo '<i class="fas fa-clock"></i> Pending Review'; break;
                                    case 'approved': echo '<i class="fas fa-check-circle"></i> Verified'; break;
                                    case 'rejected': echo '<i class="fas fa-times-circle"></i> Rejected'; break;
                                    default: echo '<i class="fas fa-question-circle"></i> Unknown Status';
                                }
                                ?>
                            </h3>
                            <p>Submitted on <?php echo date('M j, Y', strtotime($current_kyc['submitted_at'])); ?></p>
                            <?php if ($current_kyc['admin_notes']): ?>
                                <div style="margin-top: 15px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 5px;">
                                    <strong>Admin Notes:</strong> <?php echo htmlspecialchars($current_kyc['admin_notes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($current_kyc['status'] === 'rejected'): ?>
                            <p>You can submit a new KYC verification below.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!$current_kyc || $current_kyc['status'] === 'rejected'): ?>
                    <div class="kyc-card">
                        <h2>Submit KYC Verification</h2>
                        
                        <div class="requirements">
                            <h3><i class="fas fa-info-circle"></i> Requirements</h3>
                            <ul>
                                <li>Government-issued photo ID (Passport, Driver's License, or National ID)</li>
                                <li>Clear, well-lit photos of both sides of your ID</li>
                                <li>A selfie holding your ID</li>
                                <li>All information must be clearly visible and legible</li>
                                <li>Documents must not be expired</li>
                                <li>File size limit: 5MB per image</li>
                                <li>Supported formats: JPG, PNG, GIF</li>
                            </ul>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" id="kyc-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="document_type">Document Type *</label>
                                    <select id="document_type" name="document_type" required>
                                        <option value="">Select Document Type</option>
                                        <option value="passport" <?php echo ($_POST['document_type'] ?? '') === 'passport' ? 'selected' : ''; ?>>Passport</option>
                                        <option value="driver_license" <?php echo ($_POST['document_type'] ?? '') === 'driver_license' ? 'selected' : ''; ?>>Driver's License</option>
                                        <option value="national_id" <?php echo ($_POST['document_type'] ?? '') === 'national_id' ? 'selected' : ''; ?>>National ID Card</option>
                                        <option value="other" <?php echo ($_POST['document_type'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="document_number">Document Number *</label>
                                    <input type="text" id="document_number" name="document_number" 
                                           value="<?php echo htmlspecialchars($_POST['document_number'] ?? ''); ?>" 
                                           required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="last_name">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
                                           required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="date_of_birth">Date of Birth *</label>
                                    <input type="date" id="date_of_birth" name="date_of_birth" 
                                           value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="issue_date">Issue Date</label>
                                    <input type="date" id="issue_date" name="issue_date" 
                                           value="<?php echo htmlspecialchars($_POST['issue_date'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="expiry_date">Expiry Date</label>
                                    <input type="date" id="expiry_date" name="expiry_date" 
                                           value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Front of Document *</label>
                                <div class="file-upload" id="front-upload">
                                    <i class="fas fa-cloud-upload-alt fa-2x"></i>
                                    <p>Click or drag to upload front of document</p>
                                    <input type="file" id="front_image" name="front_image" accept="image/*" style="display: none;" required>
                                    <img id="front-preview" class="preview-image" alt="Front document preview">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Back of Document</label>
                                <div class="file-upload" id="back-upload">
                                    <i class="fas fa-cloud-upload-alt fa-2x"></i>
                                    <p>Click or drag to upload back of document (if applicable)</p>
                                    <input type="file" id="back_image" name="back_image" accept="image/*" style="display: none;">
                                    <img id="back-preview" class="preview-image" alt="Back document preview">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Selfie with Document</label>
                                <div class="file-upload" id="selfie-upload">
                                    <i class="fas fa-camera fa-2x"></i>
                                    <p>Click or drag to upload selfie holding document</p>
                                    <input type="file" id="selfie_image" name="selfie_image" accept="image/*" style="display: none;">
                                    <img id="selfie-preview" class="preview-image" alt="Selfie preview">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-paper-plane"></i> Submit for Verification
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // File upload handling
        document.querySelectorAll('.file-upload').forEach(uploadArea => {
            const input = uploadArea.querySelector('input[type="file"]');
            const preview = uploadArea.querySelector('img');
            
            // Click to upload
            uploadArea.addEventListener('click', () => {
                input.click();
            });
            
            // Drag and drop
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => {
                    uploadArea.classList.add('dragover');
                }, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => {
                    uploadArea.classList.remove('dragover');
                }, false);
            });
            
            uploadArea.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                if (files.length) {
                    input.files = files;
                    handleFile(input, preview);
                }
            }
            
            // File selection
            input.addEventListener('change', () => {
                handleFile(input, preview);
            });
        });
        
        function handleFile(input, preview) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Form validation
        document.getElementById('kyc-form').addEventListener('submit', function(e) {
            const requiredFields = ['document_type', 'document_number', 'first_name', 'last_name', 'date_of_birth'];
            let isValid = true;
            
            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                if (!input.value.trim()) {
                    input.style.borderColor = '#ef4444';
                    isValid = false;
                } else {
                    input.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    </script>
</body>
</html>