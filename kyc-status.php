<?php
require_once 'config.php';
require_once 'includes/functions.php';

redirect_if_not_logged_in();

$user_id = get_current_user_id();
$stmt = $pdo->prepare("SELECT * FROM kyc_submissions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$kyc_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kyc_data) {
    header('Location: profile.php?id=' . $user_id);
    exit();
}

$status_colors = [
    'pending' => 'var(--warning)',
    'approved' => 'var(--success)',
    'rejected' => 'var(--danger)'
];

$status_icons = [
    'pending' => 'hourglass-half',
    'approved' => 'check-circle',
    'rejected' => 'times-circle'
];

$status_messages = [
    'pending' => 'Your verification is currently under review. This usually takes 1-3 business days.',
    'approved' => 'Congratulations! Your identity has been verified successfully.',
    'rejected' => 'Unfortunately, your verification was rejected. Please check the reason below and resubmit.'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Status - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .kyc-status-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .status-header {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, var(--card-bg), var(--darker-bg));
            border-radius: 20px;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 1rem 2rem;
            border-radius: 30px;
            font-size: 1.2rem;
            font-weight: 600;
            margin: 1rem 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .status-pending {
            background: rgba(255, 204, 0, 0.2);
            border: 2px solid var(--warning);
            color: var(--warning);
        }
        
        .status-approved {
            background: rgba(0, 255, 157, 0.2);
            border: 2px solid var(--success);
            color: var(--success);
        }
        
        .status-rejected {
            background: rgba(255, 71, 87, 0.2);
            border: 2px solid var(--danger);
            color: var(--danger);
        }
        
        .timeline {
            position: relative;
            padding: 2rem 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border-color);
            transform: translateX(-50%);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            width: 100%;
        }
        
        .timeline-item:nth-child(odd) {
            padding-right: calc(50% + 30px);
            text-align: right;
        }
        
        .timeline-item:nth-child(even) {
            padding-left: calc(50% + 30px);
            text-align: left;
        }
        
        .timeline-dot {
            position: absolute;
            top: 10px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--border-color);
            border: 3px solid var(--card-bg);
        }
        
        .timeline-item:nth-child(odd) .timeline-dot {
            right: calc(50% - 10px);
        }
        
        .timeline-item:nth-child(even) .timeline-dot {
            left: calc(50% - 10px);
        }
        
        .timeline-content {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
        }
        
        .documents-preview {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .document-card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 1rem;
            border: 1px solid var(--border-color);
            text-align: center;
        }
        
        .document-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            margin: 1rem 0;
            border: 1px solid var(--border-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }
        
        @media (max-width: 768px) {
            .timeline::before {
                left: 20px;
            }
            
            .timeline-item {
                padding-right: 0 !important;
                padding-left: 60px !important;
                text-align: left !important;
            }
            
            .timeline-dot {
                left: 10px !important;
                right: auto !important;
            }
            
            .documents-preview {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-container">
        <div class="container">
            <div class="kyc-status-container">
                <div class="status-header">
                    <h1><i class="fas fa-id-card"></i> KYC Verification Status</h1>
                    <div class="status-badge status-<?php echo $kyc_data['status']; ?>">
                        <i class="fas fa-<?php echo $status_icons[$kyc_data['status']]; ?>"></i>
                        <?php echo ucfirst($kyc_data['status']); ?>
                    </div>
                    <p style="color: var(--text-secondary); max-width: 600px; margin: 0 auto;">
                        <?php echo $status_messages[$kyc_data['status']]; ?>
                    </p>
                </div>
                
                <?php if ($kyc_data['status'] === 'rejected' && !empty($kyc_data['rejection_reason'])): ?>
                    <div class="alert error">
                        <h4><i class="fas fa-exclamation-triangle"></i> Rejection Reason:</h4>
                        <p><?php echo htmlspecialchars($kyc_data['rejection_reason']); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Timeline -->
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-dot" style="background: var(--success);"></div>
                        <div class="timeline-content">
                            <h3><i class="fas fa-camera"></i> Photo Submitted</h3>
                            <p style="color: var(--text-secondary);">
                                Your photo was uploaded successfully
                            </p>
                            <small><?php echo date('M j, Y g:i A', strtotime($kyc_data['created_at'])); ?></small>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-dot" style="background: var(--success);"></div>
                        <div class="timeline-content">
                            <h3><i class="fas fa-file-alt"></i> Document Submitted</h3>
                            <p style="color: var(--text-secondary);">
                                Your ID document was uploaded successfully
                            </p>
                            <small><?php echo date('M j, Y g:i A', strtotime($kyc_data['created_at'])); ?></small>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-dot" style="background: <?php echo $status_colors[$kyc_data['status']]; ?>;"></div>
                        <div class="timeline-content">
                            <h3>
                                <i class="fas fa-<?php echo $status_icons[$kyc_data['status']]; ?>"></i>
                                <?php 
                                switch($kyc_data['status']) {
                                    case 'pending': echo 'Review in Progress'; break;
                                    case 'approved': echo 'Verification Approved'; break;
                                    case 'rejected': echo 'Verification Rejected'; break;
                                }
                                ?>
                            </h3>
                            <p style="color: var(--text-secondary);">
                                <?php 
                                switch($kyc_data['status']) {
                                    case 'pending': echo 'Our team is reviewing your documents'; break;
                                    case 'approved': echo 'Your identity has been verified'; break;
                                    case 'rejected': echo 'Please check the rejection reason above'; break;
                                }
                                ?>
                            </p>
                            <?php if ($kyc_data['status'] !== 'pending'): ?>
                                <small><?php echo date('M j, Y g:i A', strtotime($kyc_data['updated_at'] ?? $kyc_data['created_at'])); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Documents Preview -->
                <?php if ($kyc_data['status'] !== 'pending'): ?>
                    <div class="documents-preview">
                        <div class="document-card">
                            <h4><i class="fas fa-user"></i> Your Photo</h4>
                            <?php if (file_exists($kyc_data['photo_path'])): ?>
                                <?php if (strpos($kyc_data['photo_path'], '.pdf') !== false): ?>
                                    <i class="fas fa-file-pdf" style="font-size: 4rem; color: #dc2626; margin: 1rem 0;"></i>
                                    <p>PDF Document</p>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($kyc_data['photo_path']); ?>" 
                                         alt="Your photo" class="document-image">
                                <?php endif; ?>
                                <a href="<?php echo htmlspecialchars($kyc_data['photo_path']); ?>" 
                                   target="_blank" class="btn btn-outline" style="font-size: 0.9rem;">
                                    <i class="fas fa-eye"></i> View Full Size
                                </a>
                            <?php else: ?>
                                <p style="color: var(--text-secondary);">Photo not available</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="document-card">
                            <h4><i class="fas fa-id-card"></i> ID Document</h4>
                            <?php if (file_exists($kyc_data['document_path'])): ?>
                                <?php if (strpos($kyc_data['document_path'], '.pdf') !== false): ?>
                                    <i class="fas fa-file-pdf" style="font-size: 4rem; color: #dc2626; margin: 1rem 0;"></i>
                                    <p>PDF Document</p>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($kyc_data['document_path']); ?>" 
                                         alt="ID document" class="document-image">
                                <?php endif; ?>
                                <a href="<?php echo htmlspecialchars($kyc_data['document_path']); ?>" 
                                   target="_blank" class="btn btn-outline" style="font-size: 0.9rem;">
                                    <i class="fas fa-eye"></i> View Full Size
                                </a>
                            <?php else: ?>
                                <p style="color: var(--text-secondary);">Document not available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="profile.php?id=<?php echo $user_id; ?>" class="btn btn-outline">
                        <i class="fas fa-user"></i> Back to Profile
                    </a>
                    
                    <?php if ($kyc_data['status'] === 'rejected'): ?>
                        <a href="kyc-submit.php" class="btn btn-primary">
                            <i class="fas fa-redo"></i> Resubmit KYC
                        </a>
                    <?php elseif ($kyc_data['status'] === 'approved'): ?>
                        <a href="profile-edit.php" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Update Profile
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>