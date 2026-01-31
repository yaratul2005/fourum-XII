<?php
require_once 'config.php';
require_once 'includes/functions.php';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$user_id) {
    header('Location: index.php');
    exit();
}

$user_data = get_user_data($user_id);
if (!$user_data) {
    header('Location: index.php');
    exit();
}

// Check if current user can view this profile
$current_user_id = get_current_user_id();
$can_edit = ($current_user_id == $user_id);
$is_admin = is_admin();

// Get user's KYC status
$kyc_status = 'not_submitted';
$kyc_data = null;

if ($can_edit || $is_admin) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM kyc_submissions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $kyc_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $kyc_status = $kyc_data ? $kyc_data['status'] : 'not_submitted';
    } catch (Exception $e) {
        // Handle gracefully
    }
}

// Get user's posts and activity
$stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$stmt->execute([$user_id]);
$post_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
$stmt->execute([$user_id]);
$comment_count = $stmt->fetchColumn();

// Get user level
$user_level = get_user_level($user_data['exp']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user_data['username']); ?>'s Profile - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .profile-header {
            text-align: center;
            padding: 3rem;
            background: linear-gradient(135deg, var(--card-bg), var(--darker-bg));
            border-radius: 20px;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--primary));
        }
        
        .avatar-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid var(--primary);
            box-shadow: 0 0 30px var(--primary);
            margin: 0 auto 1.5rem;
            object-fit: cover;
            transition: all 0.4s ease;
        }
        
        .avatar-large:hover {
            transform: scale(1.05);
            box-shadow: 0 0 40px var(--primary);
        }
        
        .user-info {
            margin-bottom: 2rem;
        }
        
        .username-display {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .user-level-badge {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            background: var(--gradient-primary);
            color: var(--darker-bg);
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0.5rem 0;
            box-shadow: 0 5px 15px rgba(0, 245, 255, 0.3);
        }
        
        .kyc-status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 15px;
            font-weight: 500;
            margin: 0.5rem;
            font-size: 0.9rem;
        }
        
        .kyc-not-submitted {
            background: rgba(255, 204, 0, 0.2);
            border: 1px solid var(--warning);
            color: var(--warning);
        }
        
        .kyc-pending {
            background: rgba(0, 245, 255, 0.2);
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .kyc-approved {
            background: rgba(0, 255, 157, 0.2);
            border: 1px solid var(--success);
            color: var(--success);
        }
        
        .kyc-rejected {
            background: rgba(255, 71, 87, 0.2);
            border: 1px solid var(--danger);
            color: var(--danger);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .profile-content {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
        }
        
        .main-content {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .bio-section {
            margin-bottom: 2rem;
        }
        
        .bio-content {
            background: rgba(18, 18, 37, 0.5);
            border-radius: 10px;
            padding: 1.5rem;
            border-left: 3px solid var(--primary);
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        @media (max-width: 768px) {
            .profile-content {
                grid-template-columns: 1fr;
            }
            
            .username-display {
                font-size: 2rem;
            }
            
            .avatar-large {
                width: 120px;
                height: 120px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-container">
        <div class="container">
            <div class="profile-container">
                <div class="profile-header">
                    <img src="<?php echo !empty($user_data['avatar']) ? htmlspecialchars($user_data['avatar']) : 'assets/images/default-avatar.png'; ?>" 
                         alt="Avatar" class="avatar-large">
                    <div class="user-info">
                        <h1 class="username-display"><?php echo htmlspecialchars($user_data['username']); ?></h1>
                        <div class="user-level-badge"><?php echo $user_level['name']; ?></div>
                        
                        <?php if ($can_edit || $is_admin): ?>
                            <div class="kyc-status kyc-<?php echo $kyc_status; ?>">
                                <?php 
                                switch($kyc_status) {
                                    case 'approved':
                                        echo '<i class="fas fa-check-circle"></i> Verified';
                                        break;
                                    case 'pending':
                                        echo '<i class="fas fa-clock"></i> Verification Pending';
                                        break;
                                    case 'rejected':
                                        echo '<i class="fas fa-times-circle"></i> Verification Rejected';
                                        break;
                                    default:
                                        echo '<i class="fas fa-id-card"></i> Not Verified';
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <p style="color: var(--text-secondary); margin-top: 1rem;">
                            Member since <?php echo date('F Y', strtotime($user_data['created_at'])); ?>
                        </p>
                    </div>
                    
                    <?php if ($can_edit): ?>
                        <div class="action-buttons">
                            <a href="profile-edit.php" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit Profile
                            </a>
                            
                            <?php if ($kyc_status === 'not_submitted' || $kyc_status === 'rejected'): ?>
                                <a href="kyc-submit.php" class="btn btn-outline">
                                    <i class="fas fa-id-card"></i> Get Verified
                                </a>
                            <?php elseif ($kyc_status === 'pending'): ?>
                                <a href="kyc-status.php" class="btn btn-outline">
                                    <i class="fas fa-hourglass-half"></i> View Submission Status
                                </a>
                            <?php elseif ($kyc_status === 'approved'): ?>
                                <span class="btn btn-success" style="opacity: 0.7; cursor: default;">
                                    <i class="fas fa-check"></i> Already Verified
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo format_number($user_data['exp']); ?></div>
                        <div class="stat-label">Experience Points</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $post_count; ?></div>
                        <div class="stat-label">Posts Created</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $comment_count; ?></div>
                        <div class="stat-label">Comments</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $user_level['level']; ?></div>
                        <div class="stat-label">User Level</div>
                    </div>
                </div>
                
                <div class="profile-content">
                    <div class="main-content">
                        <?php if (!empty($user_data['bio'])): ?>
                            <div class="bio-section">
                                <h3><i class="fas fa-user-circle"></i> About Me</h3>
                                <div class="bio-content">
                                    <?php echo nl2br(htmlspecialchars($user_data['bio'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($user_data['location'])): ?>
                            <div class="bio-section">
                                <h3><i class="fas fa-map-marker-alt"></i> Location</h3>
                                <div class="bio-content">
                                    <?php echo htmlspecialchars($user_data['location']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($user_data['website'])): ?>
                            <div class="bio-section">
                                <h3><i class="fas fa-link"></i> Website</h3>
                                <div class="bio-content">
                                    <a href="<?php echo htmlspecialchars($user_data['website']); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php echo htmlspecialchars($user_data['website']); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sidebar">
                        <?php if ($is_admin): ?>
                            <div class="widget">
                                <h3><i class="fas fa-tools"></i> Admin Actions</h3>
                                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                    <a href="admin/?page=users&action=edit&id=<?php echo $user_id; ?>" class="btn btn-outline" style="text-align: center;">
                                        <i class="fas fa-user-edit"></i> Edit User
                                    </a>
                                    <?php if ($kyc_data): ?>
                                        <a href="admin/?page=kyc&action=view&id=<?php echo $kyc_data['id']; ?>" class="btn btn-outline" style="text-align: center;">
                                            <i class="fas fa-id-card"></i> Review KYC
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="widget">
                            <h3><i class="fas fa-trophy"></i> Achievements</h3>
                            <p style="color: var(--text-secondary); font-style: italic;">
                                Coming soon...
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>