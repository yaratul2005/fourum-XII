<?php
// Leaderboard Page - Show top contributors by EXP
require_once 'config.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get top users by EXP
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

try {
    $stmt = $pdo->prepare("SELECT id, username, exp, avatar, created_at FROM users ORDER BY exp DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $top_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total users count
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = $count_stmt->fetchColumn();
    $total_pages = ceil($total_users / $limit);
    
} catch (Exception $e) {
    error_log("Leaderboard query error: " . $e->getMessage());
    $top_users = [];
    $total_users = 0;
    $total_pages = 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .leaderboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .leaderboard-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .leaderboard-header h1 {
            font-family: 'Orbitron', monospace;
            color: var(--primary);
            text-shadow: 0 0 20px var(--primary);
            margin-bottom: 1rem;
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            font-family: 'Orbitron', monospace;
            margin-bottom: 0.5rem;
        }
        
        .stat-active { color: var(--success); }
        .stat-total { color: var(--primary); }
        .stat-average { color: var(--accent); }
        
        .leaderboard-table {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .table-header {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            padding: 1.5rem;
            text-align: center;
        }
        
        .table-header h2 {
            color: white;
            margin: 0;
            font-family: 'Orbitron', monospace;
        }
        
        .ranking-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .ranking-table th {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            text-align: left;
            color: var(--text-primary);
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
        }
        
        .ranking-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .ranking-table tr:hover {
            background: rgba(0, 245, 255, 0.05);
        }
        
        .rank-cell {
            width: 80px;
            text-align: center;
        }
        
        .rank-badge {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .rank-1 { background: linear-gradient(135deg, #ffd700, #ffa500); color: #000; }
        .rank-2 { background: linear-gradient(135deg, #c0c0c0, #a0a0a0); color: #000; }
        .rank-3 { background: linear-gradient(135deg, #cd7f32, #b87333); color: #fff; }
        .rank-other { background: var(--primary); color: white; }
        
        .user-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }
        
        .user-info strong {
            display: block;
            color: var(--text-primary);
        }
        
        .user-info small {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        
        .exp-cell {
            text-align: right;
            font-family: 'Orbitron', monospace;
            font-weight: 600;
            color: var(--accent);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .page-link {
            padding: 0.75rem 1.25rem;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .page-link:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .page-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-container">
        <div class="container">
            <div class="leaderboard-container">
                <div class="leaderboard-header">
                    <h1><i class="fas fa-trophy"></i> Community Leaderboard</h1>
                    <p>Top contributors ranked by Experience Points (EXP)</p>
                </div>
                
                <!-- Stats Overview -->
                <div class="stats-overview">
                    <div class="stat-card">
                        <div class="stat-number stat-active"><?php echo count($top_users); ?></div>
                        <div>Users on This Page</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number stat-total"><?php echo $total_users; ?></div>
                        <div>Total Community Members</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number stat-average"><?php 
                            echo $total_users > 0 ? round(array_sum(array_column($top_users, 'exp')) / count($top_users)) : 0;
                        ?></div>
                        <div>Average EXP</div>
                    </div>
                </div>
                
                <!-- Leaderboard Table -->
                <div class="leaderboard-table">
                    <div class="table-header">
                        <h2><i class="fas fa-crown"></i> Top Contributors</h2>
                    </div>
                    
                    <?php if (empty($top_users)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>No users found</h3>
                            <p>Be the first to join and climb the ranks!</p>
                        </div>
                    <?php else: ?>
                        <table class="ranking-table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Rank</th>
                                    <th>User</th>
                                    <th style="width: 150px;">Level</th>
                                    <th style="width: 150px; text-align: right;">EXP</th>
                                    <th style="width: 150px;">Member Since</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($top_users as $index => $user): 
                                    $actual_rank = ($page - 1) * $limit + $index + 1;
                                    $rank_class = $actual_rank <= 3 ? 'rank-' . $actual_rank : 'rank-other';
                                ?>
                                    <tr>
                                        <td class="rank-cell">
                                            <span class="rank-badge <?php echo $rank_class; ?>">
                                                <?php echo $actual_rank; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="user-cell">
                                                <img src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'assets/images/default-avatar.png'; ?>" 
                                                     alt="Avatar" class="user-avatar">
                                                <div class="user-info">
                                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                    <small>ID: <?php echo $user['id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-active">
                                                <?php echo get_user_level($user['exp']); ?>
                                            </span>
                                        </td>
                                        <td class="exp-cell">
                                            <?php echo format_number($user['exp']); ?> EXP
                                        </td>
                                        <td>
                                            <small><?php echo date('M j, Y', strtotime($user['created_at'])); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>" 
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="page-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>