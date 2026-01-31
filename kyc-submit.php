<?php
require_once 'config.php';
require_once 'includes/functions.php';

redirect_if_not_logged_in();

$user_id = get_current_user_id();
$user_data = get_user_data($user_id);

// Check if user already has a pending or approved submission
$stmt = $pdo->prepare("SELECT * FROM kyc_submissions WHERE user_id = ? AND status IN ('pending', 'approved') ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$existing_submission = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing_submission) {
    header('Location: kyc-status.php');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = intval($_POST['step'] ?? 1);
    
    if ($step === 1) {
        // Step 1: Photo submission
        if (!isset($_FILES['user_photo']) || $_FILES['user_photo']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Please upload a clear photo of yourself';
        } else {
            $photo_result = handle_kyc_file_upload($_FILES['user_photo'], 'photo', $user_id);
            if (!$photo_result['success']) {
                $errors[] = $photo_result['error'];
            } else {
                $_SESSION['kyc_photo_path'] = $photo_result['path'];
                $success = 'Photo uploaded successfully! Please proceed to document submission.';
            }
        }
    } elseif ($step === 2) {
        // Step 2: Document submission
        if (!isset($_SESSION['kyc_photo_path'])) {
            $errors[] = 'Please complete step 1 first';
        } elseif (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Please upload a valid identification document';
        } else {
            $doc_result = handle_kyc_file_upload($_FILES['document'], 'document', $user_id);
            if (!$doc_result['success']) {
                $errors[] = $doc_result['error'];
            } else {
                // Save to database
                try {
                    $stmt = $pdo->prepare("INSERT INTO kyc_submissions (user_id, photo_path, document_path, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
                    $stmt->execute([
                        $user_id,
                        $_SESSION['kyc_photo_path'],
                        $doc_result['path']
                    ]);
                    
                    // Clean up session
                    unset($_SESSION['kyc_photo_path']);
                    
                    $success = 'KYC submission completed successfully! Your verification is now pending review.';
                    header("refresh:3;kyc-status.php");
                    
                } catch (Exception $e) {
                    $errors[] = 'Failed to submit KYC: ' . $e->getMessage();
                }
            }
        }
    }
}

function handle_kyc_file_upload($file, $type, $user_id) {
    $allowed_types = [
        'photo' => ['image/jpeg', 'image/png', 'image/jpg'],
        'document' => ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf']
    ];
    
    $max_sizes = [
        'photo' => 5 * 1024 * 1024, // 5MB
        'document' => 10 * 1024 * 1024 // 10MB
    ];
    
    if (!in_array($file['type'], $allowed_types[$type])) {
        return ['success' => false, 'error' => "Invalid {$type} file type"];
    }
    
    if ($file['size'] > $max_sizes[$type]) {
        return ['success' => false, 'error' => "{$type} file too large"];
    }
    
    $upload_dir = "uploads/kyc/{$user_id}/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = "{$type}_" . time() . ".{$extension}";
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'path' => $filepath];
    } else {
        return ['success' => false, 'error' => "Failed to upload {$type}"];
    }
}
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
            padding: 2rem;
            background: linear-gradient(135deg, var(--card-bg), var(--darker-bg));
            border-radius: 20px;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }
        
        .progress-steps {
            display: flex;
            justify-content: center;
            margin: 2rem 0;
            position: relative;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 15%;
            right: 15%;
            height: 2px;
            background: var(--border-color);
            z-index: 1;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-weight: bold;
            position: relative;
            z-index: 2;
        }
        
        .step.active {
            background: var(--primary);
            color: var(--darker-bg);
            box-shadow: 0 0 15px var(--primary);
        }
        
        .step.completed {
            background: var(--success);
            color: var(--darker-bg);
        }
        
        .step-label {
            position: absolute;
            top: 45px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.8rem;
            color: var(--text-secondary);
            white-space: nowrap;
        }
        
        .kyc-form {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .form-step {
            display: none;
        }
        
        .form-step.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        .file-upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 1rem 0;
        }
        
        .file-upload-area:hover {
            border-color: var(--primary);
            background: rgba(0, 245, 255, 0.05);
        }
        
        .file-upload-area.dragover {
            border-color: var(--primary);
            background: rgba(0, 245, 255, 0.1);
            transform: scale(1.02);
        }
        
        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 10px;
            margin: 1rem 0;
            border: 1px solid var(--border-color);
        }
        
        .requirements {
            background: rgba(0, 245, 255, 0.1);
            border: 1px solid var(--primary);
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .progress-steps {
                margin: 1rem 0;
            }
            
            .progress-steps::before {
                left: 10%;
                right: 10%;
            }
            
            .step-label {
                font-size: 0.7rem;
                top: 40px;
            }
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
                    <p>Verify your identity to unlock premium features and increased trust</p>
                </div>
                
                <?php if ($errors): ?>
                    <div class="alert error">
                        <h4><i class="fas fa-exclamation-circle"></i> Please correct the following:</h4>
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
                
                <!-- Progress Steps -->
                <div class="progress-steps">
                    <div class="step active" id="step1-indicator">
                        1
                        <div class="step-label">Your Photo</div>
                    </div>
                    <div class="step" id="step2-indicator">
                        2
                        <div class="step-label">ID Document</div>
                    </div>
                </div>
                
                <div class="kyc-form">
                    <!-- Step 1: Photo Submission -->
                    <div class="form-step active" id="step1">
                        <h2><i class="fas fa-camera"></i> Step 1: Upload Your Photo</h2>
                        <p>Please upload a clear, well-lit photo of yourself holding your ID document.</p>
                        
                        <div class="requirements">
                            <h4><i class="fas fa-info-circle"></i> Photo Requirements:</h4>
                            <ul>
                                <li>Clear, high-quality image</li>
                                <li>Good lighting conditions</li>
                                <li>Face clearly visible</li>
                                <li>Holding your ID document</li>
                                <li>File size under 5MB</li>
                                <li>JPG or PNG format only</li>
                            </ul>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" id="photoForm">
                            <input type="hidden" name="step" value="1">
                            
                            <div class="form-group">
                                <label class="file-upload-area" id="photoDropArea">
                                    <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; margin-bottom: 1rem; color: var(--primary);"></i>
                                    <p>Click or drag your photo here</p>
                                    <p style="font-size: 0.9rem; color: var(--text-secondary);">JPG, PNG files only (Max 5MB)</p>
                                    <input type="file" id="user_photo" name="user_photo" accept="image/*" style="display: none;" required>
                                </label>
                            </div>
                            
                            <div id="photoPreview"></div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" id="nextStepBtn" disabled>
                                    <i class="fas fa-arrow-right"></i> Next Step
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Step 2: Document Submission -->
                    <div class="form-step" id="step2">
                        <h2><i class="fas fa-file-alt"></i> Step 2: Upload ID Document</h2>
                        <p>Please upload a clear scan or photo of your government-issued ID document.</p>
                        
                        <div class="requirements">
                            <h4><i class="fas fa-info-circle"></i> Document Requirements:</h4>
                            <ul>
                                <li>Government-issued ID (Passport, Driver's License, etc.)</li>
                                <li>Full document visible</li>
                                <li>Clear and readable text</li>
                                <li>No glare or shadows</li>
                                <li>File size under 10MB</li>
                                <li>JPG, PNG, or PDF format</li>
                            </ul>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" id="documentForm">
                            <input type="hidden" name="step" value="2">
                            
                            <div class="form-group">
                                <label class="file-upload-area" id="documentDropArea">
                                    <i class="fas fa-file-upload" style="font-size: 3rem; margin-bottom: 1rem; color: var(--primary);"></i>
                                    <p>Click or drag your ID document here</p>
                                    <p style="font-size: 0.9rem; color: var(--text-secondary);">JPG, PNG, PDF files only (Max 10MB)</p>
                                    <input type="file" id="document" name="document" accept="image/*,.pdf" style="display: none;" required>
                                </label>
                            </div>
                            
                            <div id="documentPreview"></div>
                            
                            <div class="form-group" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                <button type="button" class="btn btn-outline" onclick="showStep(1)">
                                    <i class="fas fa-arrow-left"></i> Previous
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Submit for Verification
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // File upload handling
        function setupFileUpload(inputId, dropAreaId, previewId, fileType) {
            const input = document.getElementById(inputId);
            const dropArea = document.getElementById(dropAreaId);
            const preview = document.getElementById(previewId);
            const nextBtn = document.getElementById('nextStepBtn');
            
            // Click to select file
            dropArea.addEventListener('click', () => input.click());
            
            // Drag and drop
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                dropArea.classList.add('dragover');
            }
            
            function unhighlight() {
                dropArea.classList.remove('dragover');
            }
            
            // Handle file selection
            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    // Validate file
                    const maxSize = fileType === 'photo' ? 5 * 1024 * 1024 : 10 * 1024 * 1024;
                    const allowedTypes = fileType === 'photo' ? 
                        ['image/jpeg', 'image/png', 'image/jpg'] :
                        ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
                    
                    if (!allowedTypes.includes(file.type)) {
                        alert(`Please select a valid ${fileType} file`);
                        return;
                    }
                    
                    if (file.size > maxSize) {
                        alert(`${fileType} file is too large`);
                        return;
                    }
                    
                    // Preview
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.innerHTML = `
                                <div style="text-align: center; margin: 1rem 0;">
                                    <img src="${e.target.result}" class="preview-image" alt="${fileType} preview">
                                    <p style="color: var(--success);"><i class="fas fa-check-circle"></i> ${file.name}</p>
                                </div>
                            `;
                        };
                        reader.readAsDataURL(file);
                    } else {
                        preview.innerHTML = `
                            <div style="text-align: center; margin: 1rem 0;">
                                <i class="fas fa-file-pdf" style="font-size: 3rem; color: #dc2626; margin-bottom: 1rem;"></i>
                                <p style="color: var(--success);"><i class="fas fa-check-circle"></i> ${file.name}</p>
                                <p style="color: var(--text-secondary);">PDF document ready for upload</p>
                            </div>
                        `;
                    }
                    
                    // Enable next button for photo step
                    if (fileType === 'photo' && nextBtn) {
                        nextBtn.disabled = false;
                    }
                }
            });
        }
        
        // Step navigation
        function showStep(step) {
            document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.step').forEach(s => s.classList.remove('active', 'completed'));
            
            document.getElementById(`step${step}`).classList.add('active');
            document.getElementById(`step${step}-indicator`).classList.add('active');
            
            // Mark previous steps as completed
            for (let i = 1; i < step; i++) {
                document.getElementById(`step${i}-indicator`).classList.add('completed');
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            setupFileUpload('user_photo', 'photoDropArea', 'photoPreview', 'photo');
            setupFileUpload('document', 'documentDropArea', 'documentPreview', 'document');
            
            // Next step button
            document.getElementById('nextStepBtn')?.addEventListener('click', function(e) {
                e.preventDefault();
                if (document.getElementById('user_photo').files.length > 0) {
                    showStep(2);
                }
            });
        });
    </script>
</body>
</html>