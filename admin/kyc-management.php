<?php
// Admin KYC Management Panel
require_once '../config.php';
require_once '../includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check admin access
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}

$current_user = get_user_data(get_current_user_id());
if ($current_user['username'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$message = '';
$message_type = '';

// Handle KYC actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $kyc_id = (int)$_POST['kyc_id'];
        $action = $_POST['action'];
        $admin_notes = trim($_POST['admin_notes'] ?? '');
        
        // Get KYC record
        $stmt = $pdo->prepare("SELECT * FROM kyc_documents WHERE id = ?");
        $stmt->execute([$kyc_id]);
        $kyc_record = $stmt->fetch();
        
        if (!$kyc_record) {
            throw new Exception('KYC record not found.');
        }
        
        $new_status = '';
        switch ($action) {
            case 'approve':
                $new_status = 'approved';
                // Update user KYC status
                $stmt = $pdo->prepare("UPDATE users SET kyc_status = 'verified', kyc_verified_at = NOW() WHERE id = ?");
                $stmt->execute([$kyc_record['user_id']]);
                // Send notification to user
                send_user_notification($kyc_record['user_id'], 'KYC Approved', 'Your KYC verification has been approved! You can now create categories.', 'kyc_update', $kyc_id);
                break;
                
            case 'reject':
                $new_status = 'rejected';
                // Update user KYC status
                $stmt = $pdo->prepare("UPDATE users SET kyc_status = 'rejected' WHERE id = ?");
                $stmt->execute([$kyc_record['user_id']]);
                // Send notification to user
                send_user_notification($kyc_record['user_id'], 'KYC Rejected', 'Your KYC verification has been rejected. Please check the notes and resubmit.', 'kyc_update', $kyc_id);
                break;
                
            case 'request_changes':
                $new_status = 'review_required';
                // Send notification to user
                send_user_notification($kyc_record['user_id'], 'KYC Review Required', 'Additional information is needed for your KYC verification. Please check the notes.', 'kyc_update', $kyc_id);
                break;
                
            default:
                throw new Exception('Invalid action.');
        }
        
        // Update KYC record
        $stmt = $pdo->prepare("UPDATE kyc_documents SET status = ?, admin_notes = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
        $stmt->execute([$new_status, $admin_notes, $current_user['id'], $kyc_id]);
        
        // Log the action
        log_kyc_action($kyc_id, $action, $current_user['id'], $admin_notes);
        
        $message = "KYC verification {$action}d successfully!";
        $message_type = 'success';
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get KYC records with filtering
$status_filter = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clause = '';
$params = [];

if ($status_filter !== 'all') {
    $where_clause = "WHERE kd.status = ?";
    $params[] = $status_filter;
}

// Get total count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM kyc_documents kd {$where_clause}");
$stmt->execute($params);
$total_records = $stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// Get KYC records
$stmt = $pdo->prepare("
    SELECT kd.*, u.username, u.email, u.exp,
           reviewer.username as reviewer_name
    FROM kyc_documents kd
    JOIN users u ON kd.user_id = u.id
    LEFT JOIN users reviewer ON kd.reviewed_by = reviewer.id
    {$where_clause}
    ORDER BY kd.submitted_at DESC
    LIMIT {$limit} OFFSET {$offset}
");
$stmt->execute($params);
$kyc_records = $stmt->fetchAll();

// Get statistics
$stats_stmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'review_required' THEN 1 ELSE 0 END) as review_required,
        COUNT(*) as total
    FROM kyc_documents
");
$stats = $stats_stmt->fetch();
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border: 1px solid var(--border-color);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-pending { color: #f59e0b; }
        .stat-approved { color: #10b981; }
        .stat-rejected { color: #ef4444; }
        .stat-review { color: #3b82f6; }
        .stat-total { color: var(--text-primary); }
        
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .filter-btn.active {
            background: var(--primary);
            color: var(--dark-bg);
        }
        
        .kyc-record {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }
        
        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .status-approved { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status-rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .status-review { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        
        .document-images {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .document-image {
            text-align: center;
        }
        
        .document-image img {
            max-width: 100%;
            max-height: 150px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        
        .document-label {
            margin-top: 10px;
            font-weight: 600;
            color: var(--text-secondary);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        .btn-small {
            padding: 8px 15px;
            font-size: 0.9rem;
            border-radius: 5px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
        
        .pagination a {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        
        .pagination a:hover {
            background: var(--primary);
            color: var(--dark-bg);
        }
        
        .pagination .current {
            background: var(--primary);
            color: var(--dark-bg);
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1><i class="fas fa-id-card"></i> KYC Management</h1>
                <p>Review and manage user identity verifications</p>
            </header>
            
            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number stat-pending"><?php echo $stats['pending']; ?></div>
                        <div>Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number stat-approved"><?php echo $stats['approved']; ?></div>
                        <div>Approved</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number stat-rejected"><?php echo $stats['rejected']; ?></div>
                        <div>Rejected</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number stat-review"><?php echo $stats['review_required']; ?></div>
                        <div>Review Required</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number stat-total"><?php echo $stats['total']; ?></div>
                        <div>Total Submissions</div>
                    </div>
                </div>
                
                <!-- Filter Bar -->
                <div class="filter-bar">
                    <a href="?status=all" class="filter-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                        All (<?php echo $stats['total']; ?>)
                    </a>
                    <a href="?status=pending" class="filter-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                        Pending (<?php echo $stats['pending']; ?>)
                    </a>
                    <a href="?status=approved" class="filter-btn <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                        Approved (<?php echo $stats['approved']; ?>)
                    </a>
                    <a href="?status=rejected" class="filter-btn <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                        Rejected (<?php echo $stats['rejected']; ?>)
                    </a>
                    <a href="?status=review_required" class="filter-btn <?php echo $status_filter === 'review_required' ? 'active' : ''; ?>">
                        Review Required (<?php echo $stats['review_required']; ?>)
                    </a>
                </div>
                
                <!-- KYC Records -->
                <?php if (empty($kyc_records)): ?>
                    <div class="kyc-record" style="text-align: center; padding: 40px;">
                        <i class="fas fa-inbox fa-3x" style="color: var(--text-secondary); margin-bottom: 20px;"></i>
                        <h3>No KYC submissions found</h3>
                        <p>There are no <?php echo $status_filter === 'all' ? '' : $status_filter; ?> KYC submissions to display.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($kyc_records as $record): ?>
                        <div class="kyc-record">
                            <div class="record-header">
                                <div class="user-info">
                                    <div>
                                        <h3><?php echo htmlspecialchars($record['username']); ?></h3>
                                        <p style="color: var(--text-secondary); margin: 5px 0;">
                                            <?php echo htmlspecialchars($record['email']); ?> • 
                                            EXP: <?php echo number_format($record['exp']); ?>
                                        </p>
                                        <p style="color: var(--text-secondary); font-size: 0.9rem;">
                                            Submitted: <?php echo date('M j, Y g:i A', strtotime($record['submitted_at'])); ?>
                                            <?php if ($record['reviewed_at']): ?>
                                                • Reviewed: <?php echo date('M j, Y g:i A', strtotime($record['reviewed_at'])); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <span class="status-badge status-<?php echo str_replace('_', '-', $record['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                    </span>
                                    <?php if ($record['reviewer_name']): ?>
                                        <div style="margin-top: 10px; font-size: 0.9rem; color: var(--text-secondary);">
                                            Reviewed by: <?php echo htmlspecialchars($record['reviewer_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Document Details -->
                            <div style="margin: 20px 0;">
                                <h4>Document Information</h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                    <div>
                                        <strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $record['document_type'])); ?>
                                    </div>
                                    <div>
                                        <strong>Number:</strong> <?php echo htmlspecialchars($record['document_number']); ?>
                                    </div>
                                    <div>
                                        <strong>Name:</strong> <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>
                                    </div>
                                    <div>
                                        <strong>DOB:</strong> <?php echo date('M j, Y', strtotime($record['date_of_birth'])); ?>
                                    </div>
                                    <?php if ($record['issue_date']): ?>
                                        <div>
                                            <strong>Issue Date:</strong> <?php echo date('M j, Y', strtotime($record['issue_date'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($record['expiry_date']): ?>
                                        <div>
                                            <strong>Expiry Date:</strong> <?php echo date('M j, Y', strtotime($record['expiry_date'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Document Images -->
                            <div class="document-images">
                                <div class="document-image">
                                    <img src="../<?php echo htmlspecialchars($record['front_image']); ?>" alt="Front of document">
                                    <div class="document-label">Front of Document</div>
                                </div>
                                <?php if ($record['back_image']): ?>
                                    <div class="document-image">
                                        <img src="../<?php echo htmlspecialchars($record['back_image']); ?>" alt="Back of document">
                                        <div class="document-label">Back of Document</div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($record['selfie_image']): ?>
                                    <div class="document-image">
                                        <img src="../<?php echo htmlspecialchars($record['selfie_image']); ?>" alt="Selfie with document">
                                        <div class="document-label">Selfie with Document</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Admin Notes -->
                            <?php if ($record['admin_notes']): ?>
                                <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; margin: 20px 0;">
                                    <strong>Admin Notes:</strong>
                                    <p style="margin: 10px 0 0 0;"><?php echo nl2br(htmlspecialchars($record['admin_notes'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Action Form -->
                            <?php if ($record['status'] === 'pending' || $record['status'] === 'review_required'): ?>
                                <form method="POST" class="action-form" data-kyc-id="<?php echo $record['id']; ?>">
                                    <input type="hidden" name="kyc_id" value="<?php echo $record['id']; ?>">
                                    
                                    <div style="margin: 20px 0;">
                                        <label for="admin_notes_<?php echo $record['id']; ?>">Admin Notes:</label>
                                        <textarea id="admin_notes_<?php echo $record['id']; ?>" name="admin_notes" 
                                                  placeholder="Add notes for the user..." 
                                                  style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-primary);"><?php echo htmlspecialchars($record['admin_notes'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-small">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-small">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                        <button type="submit" name="action" value="request_changes" class="btn btn-warning btn-small">
                                            <i class="fas fa-edit"></i> Request Changes
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $page - 1; ?>">&laquo; Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?status=<?php echo $status_filter; ?>&page=<?php echo $page + 1; ?>">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Confirm actions
        document.querySelectorAll('.action-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const action = e.submitter.value;
                const kycId = form.dataset.kycId;
                
                let message = '';
                switch(action) {
                    case 'approve':
                        message = 'Are you sure you want to APPROVE this KYC verification?';
                        break;
                    case 'reject':
                        message = 'Are you sure you want to REJECT this KYC verification?';
                        break;
                    case 'request_changes':
                        message = 'Are you sure you want to request changes for this KYC verification?';
                        break;
                }
                
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>