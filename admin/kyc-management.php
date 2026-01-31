<?php
require_once '../config.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    header('Location: ../login.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

$message = '';
$message_type = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_kyc'])) {
        $rejection_reason = trim($_POST['rejection_reason'] ?? '');
        
        try {
            $stmt = $pdo->prepare("UPDATE kyc_submissions SET status = 'approved', rejection_reason = NULL, updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$id])) {
                // Award EXP for verification
                $stmt = $pdo->prepare("SELECT user_id FROM kyc_submissions WHERE id = ?");
                $stmt->execute([$id]);
                $user_id = $stmt->fetchColumn();
                
                award_exp($user_id, EXP_KYC_APPROVED, 'KYC verification approved');
                
                $message = 'KYC submission approved successfully!';
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error approving KYC: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['reject_kyc'])) {
        $rejection_reason = trim($_POST['rejection_reason'] ?? '');
        
        if (empty($rejection_reason)) {
            $message = 'Rejection reason is required';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE kyc_submissions SET status = 'rejected', rejection_reason = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt->execute([$rejection_reason, $id])) {
                    $message = 'KYC submission rejected';
                    $message_type = 'success';
                }
            } catch (Exception $e) {
                $message = 'Error rejecting KYC: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Get KYC submissions
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where_clause = '';
$params = [];

// Filter by status
$status_filter = $_GET['status'] ?? '';
if ($status_filter) {
    $where_clause = "WHERE status = ?";
    $params[] = $status_filter;
}

$stmt = $pdo->prepare("SELECT k.*, u.username, u.email FROM kyc_submissions k JOIN users u ON k.user_id = u.id {$where_clause} ORDER BY k.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$limit, $offset]));
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM kyc_submissions k JOIN users u ON k.user_id = u.id {$where_clause}");
$count_stmt->execute($params);
$total_submissions = $count_stmt->fetchColumn();
$total_pages = ceil($total_submissions / $limit);

// Get specific submission if viewing details
$submission = null;
if ($id && $action === 'view') {
    $stmt = $pdo->prepare("SELECT k.*, u.username, u.email, u.avatar FROM kyc_submissions k JOIN users u ON k.user_id = u.id WHERE k.id = ?");
    $stmt->execute([$id]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Management - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: rgba(255, 204, 0, 0.2);
            border: 1px solid var(--admin-warning);
            color: var(--admin-warning);
        }
        
        .status-approved {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid var(--admin-success);
            color: var(--admin-success);
        }
        
        .status-rejected {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid var(--admin-danger);
            color: var(--admin-danger);
        }
        
        .document-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            border: 1px solid var(--admin-border);
            margin: 0.5rem 0;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: var(--admin-bg);
            border-radius: 15px;
            padding: 2rem;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid var(--admin-border);
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .stat-card {
            background: var(--admin-darker);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1><i class="fas fa-id-card"></i> KYC Management</h1>
                <div>
                    <a href="?status=pending" class="btn btn-warning">
                        <i class="fas fa-clock"></i> Pending (<?php 
                            $stmt = $pdo->query("SELECT COUNT(*) FROM kyc_submissions WHERE status = 'pending'");
                            echo $stmt->fetchColumn();
                        ?>)
                    </a>
                    <a href="?status=approved" class="btn btn-success">
                        <i class="fas fa-check"></i> Approved
                    </a>
                    <a href="?status=rejected" class="btn btn-danger">
                        <i class="fas fa-times"></i> Rejected
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Overview -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number" style="color: var(--admin-warning);">
                        <?php 
                        $stmt = $pdo->query("SELECT COUNT(*) FROM kyc_submissions WHERE status = 'pending'");
                        echo $stmt->fetchColumn();
                        ?>
                    </div>
                    <p>Pending Review</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: var(--admin-success);">
                        <?php 
                        $stmt = $pdo->query("SELECT COUNT(*) FROM kyc_submissions WHERE status = 'approved'");
                        echo $stmt->fetchColumn();
                        ?>
                    </div>
                    <p>Approved</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: var(--admin-danger);">
                        <?php 
                        $stmt = $pdo->query("SELECT COUNT(*) FROM kyc_submissions WHERE status = 'rejected'");
                        echo $stmt->fetchColumn();
                        ?>
                    </div>
                    <p>Rejected</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $stmt = $pdo->query("SELECT COUNT(*) FROM kyc_submissions");
                        echo $stmt->fetchColumn();
                        ?>
                    </div>
                    <p>Total Submissions</p>
                </div>
            </div>

            <?php if ($action === 'view' && $submission): ?>
                <!-- Submission Detail View -->
                <div class="admin-content">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-user"></i> KYC Submission Details</h2>
                            <div>
                                <span class="status-badge status-<?php echo $submission['status']; ?>">
                                    <?php echo ucfirst($submission['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                                <div>
                                    <h3><i class="fas fa-user"></i> User Information</h3>
                                    <p><strong>Username:</strong> <?php echo htmlspecialchars($submission['username']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($submission['email']); ?></p>
                                    <p><strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($submission['created_at'])); ?></p>
                                    <?php if ($submission['updated_at']): ?>
                                        <p><strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($submission['updated_at'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <h3><i class="fas fa-file-alt"></i> Submission Status</h3>
                                    <p><strong>Status:</strong> 
                                        <span class="status-badge status-<?php echo $submission['status']; ?>">
                                            <?php echo ucfirst($submission['status']); ?>
                                        </span>
                                    </p>
                                    <?php if ($submission['rejection_reason']): ?>
                                        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--admin-danger); border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                                            <h4 style="color: var(--admin-danger);"><i class="fas fa-exclamation-circle"></i> Rejection Reason</h4>
                                            <p><?php echo htmlspecialchars($submission['rejection_reason']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <h3><i class="fas fa-images"></i> Submitted Documents</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1rem;">
                                <div>
                                    <h4><i class="fas fa-camera"></i> User Photo</h4>
                                    <?php if (file_exists($submission['photo_path'])): ?>
                                        <?php if (strpos($submission['photo_path'], '.pdf') !== false): ?>
                                            <i class="fas fa-file-pdf" style="font-size: 4rem; color: #dc2626; margin: 1rem 0;"></i>
                                            <p>PDF Document - <a href="<?php echo htmlspecialchars($submission['photo_path']); ?>" target="_blank">View PDF</a></p>
                                        <?php else: ?>
                                            <img src="<?php echo '../' . htmlspecialchars($submission['photo_path']); ?>" 
                                                 alt="User photo" class="document-preview" 
                                                 onclick="openModal('<?php echo htmlspecialchars($submission['photo_path']); ?>')">
                                            <p><a href="<?php echo '../' . htmlspecialchars($submission['photo_path']); ?>" target="_blank">View Full Size</a></p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p style="color: var(--admin-text-secondary);">Photo not available</p>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <h4><i class="fas fa-id-card"></i> ID Document</h4>
                                    <?php if (file_exists($submission['document_path'])): ?>
                                        <?php if (strpos($submission['document_path'], '.pdf') !== false): ?>
                                            <i class="fas fa-file-pdf" style="font-size: 4rem; color: #dc2626; margin: 1rem 0;"></i>
                                            <p>PDF Document - <a href="<?php echo htmlspecialchars($submission['document_path']); ?>" target="_blank">View PDF</a></p>
                                        <?php else: ?>
                                            <img src="<?php echo '../' . htmlspecialchars($submission['document_path']); ?>" 
                                                 alt="ID document" class="document-preview" 
                                                 onclick="openModal('<?php echo htmlspecialchars($submission['document_path']); ?>')">
                                            <p><a href="<?php echo '../' . htmlspecialchars($submission['document_path']); ?>" target="_blank">View Full Size</a></p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p style="color: var(--admin-text-secondary);">Document not available</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($submission['status'] === 'pending'): ?>
                                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--admin-border);">
                                    <h3><i class="fas fa-gavel"></i> Review Actions</h3>
                                    
                                    <form method="POST" style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
                                        <input type="hidden" name="id" value="<?php echo $submission['id']; ?>">
                                        
                                        <button type="submit" name="approve_kyc" class="btn btn-success">
                                            <i class="fas fa-check"></i> Approve Verification
                                        </button>
                                        
                                        <button type="button" class="btn btn-danger" onclick="document.getElementById('rejectReason').style.display = 'block'">
                                            <i class="fas fa-times"></i> Reject Verification
                                        </button>
                                    </form>
                                    
                                    <div id="rejectReason" style="display: none; margin-top: 1rem;">
                                        <form method="POST">
                                            <input type="hidden" name="id" value="<?php echo $submission['id']; ?>">
                                            <div class="form-group">
                                                <label>Rejection Reason <span style="color: var(--admin-danger;">*</span></label>
                                                <textarea name="rejection_reason" class="form-control" rows="3" 
                                                          placeholder="Please provide a clear reason for rejection..." required></textarea>
                                            </div>
                                            <div class="form-group">
                                                <button type="submit" name="reject_kyc" class="btn btn-danger">
                                                    <i class="fas fa-times"></i> Confirm Rejection
                                                </button>
                                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('rejectReason').style.display = 'none'">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div style="margin-top: 2rem; text-align: center;">
                                <a href="index.php?page=kyc" class="btn btn-outline">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                                <a href="../profile.php?id=<?php echo $submission['user_id']; ?>" class="btn btn-primary" target="_blank">
                                    <i class="fas fa-user"></i> View User Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Submissions List -->
                <div class="admin-content">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-list"></i> KYC Submissions</h2>
                            <?php if ($status_filter): ?>
                                <span style="color: var(--admin-text-secondary);">
                                    Filtering by: <?php echo ucfirst($status_filter); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body">
                            <?php if (empty($submissions)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>No KYC submissions found</p>
                                    <?php if ($status_filter): ?>
                                        <a href="index.php?page=kyc" class="btn btn-primary">View All Submissions</a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="admin-table">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Status</th>
                                                <th>Submitted</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($submissions as $sub): ?>
                                                <tr>
                                                    <td>
                                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                            <img src="<?php echo !empty($sub['avatar']) ? '../' . htmlspecialchars($sub['avatar']) : '../assets/images/default-avatar.png'; ?>" 
                                                                 alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%;">
                                                            <div>
                                                                <div style="font-weight: 500;"><?php echo htmlspecialchars($sub['username']); ?></div>
                                                                <div style="font-size: 0.85rem; color: var(--admin-text-secondary);">
                                                                    <?php echo htmlspecialchars($sub['email']); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $sub['status']; ?>">
                                                            <?php echo ucfirst($sub['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($sub['created_at'])); ?></td>
                                                    <td>
                                                        <a href="?page=kyc&action=view&id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-outline">
                                                            <i class="fas fa-eye"></i> Review
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <div class="pagination">
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <a href="?page=kyc&page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" 
                                               class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Image Modal -->
    <div class="modal" id="imageModal">
        <div class="modal-content" style="text-align: center;">
            <button onclick="closeModal()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: var(--admin-text-primary); font-size: 1.5rem; cursor: pointer;">×</button>
            <img id="modalImage" src="" alt="Document" style="max-width: 100%; max-height: 80vh;">
        </div>
    </div>

    <script>
        function openModal(imageSrc) {
            document.getElementById('modalImage').src = '../' + imageSrc;
            document.getElementById('imageModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('imageModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>