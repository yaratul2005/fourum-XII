<nav class="admin-sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-shield-alt"></i> ADMIN PANEL</h2>
        <span>V5.0</span>
    </div>
    
    <div class="sidebar-menu">
        <!-- Dashboard -->
        <div class="menu-section">
            <h3>Main</h3>
            <a href="index.php?page=dashboard" class="nav-item <?php echo (!isset($_GET['page']) || $_GET['page'] === 'dashboard') ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
        </div>
        
        <!-- User Management -->
        <div class="menu-section">
            <h3>User Management</h3>
            <a href="index.php?page=users" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] === 'users') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Users
            </a>
            <a href="index.php?page=kyc" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] === 'kyc') ? 'active' : ''; ?>">
                <i class="fas fa-id-card"></i> KYC Verification
            </a>
        </div>
        
        <!-- Content Management -->
        <div class="menu-section">
            <h3>Content</h3>
            <a href="index.php?page=posts" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] === 'posts') ? 'active' : ''; ?>">
                <i class="fas fa-comments"></i> Posts & Comments
            </a>
            <a href="index.php?page=categories" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] === 'categories') ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="index.php?page=reports" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] === 'reports') ? 'active' : ''; ?>">
                <i class="fas fa-flag"></i> Reports
            </a>
        </div>
        
        <!-- System Settings -->
        <div class="menu-section">
            <h3>System</h3>
            <a href="index.php?page=smtp" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] === 'smtp') ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i> SMTP Settings
            </a>
            <a href="index.php?page=email-test" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] === 'email-test') ? 'active' : ''; ?>">
                <i class="fas fa-flask"></i> Email Testing
            </a>
            <a href="index.php?page=google-auth" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] === 'google-auth') ? 'active' : ''; ?>">
                <i class="fab fa-google"></i> Google Auth
            </a>
            <a href="index.php?page=settings" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] === 'settings') ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> General Settings
            </a>
            <a href="index.php?page=backup" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] === 'backup') ? 'active' : ''; ?>">
                <i class="fas fa-database"></i> Backup
            </a>
        </div>
    </div>
    
    <div class="sidebar-footer">
        <a href="../index.php" class="nav-item">
            <i class="fas fa-arrow-left"></i> Back to Site
        </a>
        <a href="../logout.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</nav>

<style>
.admin-sidebar {
    width: 280px;
    background: var(--admin-sidebar-bg);
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    border-right: 1px solid var(--admin-border);
    overflow-y: auto;
    z-index: 1000;
}

.sidebar-header {
    padding: 25px 20px;
    background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
    color: white;
    text-align: center;
}

.sidebar-header h2 {
    margin: 0 0 5px 0;
    font-size: 1.2rem;
    font-weight: 600;
}

.sidebar-header span {
    font-size: 0.9rem;
    opacity: 0.9;
}

.sidebar-menu {
    padding: 20px 0;
}

.menu-section {
    margin-bottom: 25px;
}

.menu-section h3 {
    padding: 0 20px 10px 20px;
    color: var(--admin-text-secondary);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-bottom: 1px solid var(--admin-border);
    margin: 0 0 10px 0;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: var(--admin-text-secondary);
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.nav-item:hover {
    background: var(--admin-hover-bg);
    color: var(--admin-text-primary);
    border-left-color: var(--admin-primary);
}

.nav-item.active {
    background: var(--admin-active-bg);
    color: var(--admin-primary);
    border-left-color: var(--admin-primary);
    font-weight: 500;
}

.nav-item i {
    width: 20px;
    text-align: center;
    font-size: 1.1rem;
}

.sidebar-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px 0;
    border-top: 1px solid var(--admin-border);
    background: var(--admin-sidebar-bg);
}

.sidebar-footer .nav-item {
    margin: 5px 0;
}
</style>