<?php
require_once 'cache-manager.php';
require_once 'db-functions.php'; // For get_setting and other functions

// Set cache headers for static pages - only if no output has been sent
if (!defined('NO_CACHE') && !headers_sent()) {
    CacheManager::setBrowserCache(3600, true); // Cache for 1 hour
} elseif (defined('NO_CACHE') && !headers_sent()) {
    CacheManager::setNoCache();
}
// If headers already sent, we silently continue without setting cache headers

// Get admin-controlled header settings
$header_title = get_setting('header_title', 'FUROM');
$header_subtitle = get_setting('header_subtitle', 'Futuristic Community Platform');
$show_header_logo = filter_var(get_setting('show_header_logo', true), FILTER_VALIDATE_BOOLEAN);
$header_custom_css = get_setting('header_custom_css', '');

// Get appearance settings
$primary_color = get_site_appearance('primary_color', '#00f5ff');
$secondary_color = get_site_appearance('secondary_color', '#ff00ff');
$enable_particles = filter_var(get_site_appearance('enable_particles', true), FILTER_VALIDATE_BOOLEAN);
$custom_header_html = get_site_appearance('custom_header_html', '');
?>

<header class="cyber-header">
    <div class="container">
        <div class="header-content">
            <!-- Site Branding with Admin Controls -->
            <div class="logo">
                <?php if ($show_header_logo): ?>
                    <h1><i class="fas fa-robot"></i> <?php echo htmlspecialchars($header_title); ?></h1>
                <?php else: ?>
                    <h1><?php echo htmlspecialchars($header_title); ?></h1>
                <?php endif; ?>
                <span class="tagline"><?php echo htmlspecialchars($header_subtitle); ?></span>
            </div>
            
            <!-- Custom Header Content (Admin Controlled) -->
            <?php if (!empty($custom_header_html)): ?>
                <div class="custom-header-content">
                    <?php echo $custom_header_html; ?>
                </div>
            <?php endif; ?>
            
            <!-- Enhanced Navigation -->
            <nav class="main-nav">
                <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="categories.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <?php if (is_logged_in()): ?>
                    <a href="create-post.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'create-post.php' ? 'active' : ''; ?>">
                        <i class="fas fa-plus-circle"></i> Create Post
                    </a>
                <?php endif; ?>
            </nav>
            
            <!-- User Actions -->
            <div class="user-actions">
                <?php if (is_logged_in()): ?>
                    <?php $current_user = get_user_data(get_current_user_id()); ?>
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <img src="<?php echo $current_user['avatar'] ?: 'assets/images/default-avatar.png'; ?>" 
                                 alt="Avatar" class="avatar-small">
                            <span class="username"><?php echo htmlspecialchars($current_user['username']); ?></span>
                            <span class="exp-badge"><?php echo format_number($current_user['exp']); ?> EXP</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="profile.php?id=<?php echo $current_user['id']; ?>"><i class="fas fa-user"></i> Profile</a>
                            <?php if (is_admin()): ?>
                                <a href="admin/"><i class="fas fa-shield-alt"></i> Admin Panel</a>
                            <?php endif; ?>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<!-- Global Loading Overlay -->
<div id="global-loading" class="loading-state" style="display: none;">
    <div class="loading-spinner"></div>
    <div class="loading-message">Loading Furom...</div>
</div>

<!-- Particles Background (Admin controlled) -->
<?php if ($enable_particles): ?>
<div id="particles-js"></div>
<?php endif; ?>

<style>
:root {
    --primary: <?php echo $primary_color; ?>;
    --secondary: <?php echo $secondary_color; ?>;
}

<?php echo $header_custom_css; ?>
</style>

<script>
// Global loading state management
window.furom = window.furom || {};
furom.loading = {
    show: function(message = 'Loading...') {
        const loader = document.getElementById('global-loading');
        if (loader) {
            loader.querySelector('.loading-message').textContent = message;
            loader.style.display = 'flex';
        }
    },
    
    hide: function() {
        const loader = document.getElementById('global-loading');
        if (loader) {
            loader.style.display = 'none';
        }
    }
};

// Show loading on navigation
document.addEventListener('DOMContentLoaded', function() {
    // Add loading to all internal links
    document.querySelectorAll('a[href^="/"], a[href^="./"], a:not([href^="http"])').forEach(link => {
        link.addEventListener('click', function() {
            if (!this.hasAttribute('data-no-loading')) {
                furom.loading.show();
            }
        });
    });
    
    // Hide loading when page loads
    window.addEventListener('load', function() {
        furom.loading.hide();
    });
});

// Service Worker registration for advanced caching
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
            .then(function(registration) {
                console.log('ServiceWorker registered with scope:', registration.scope);
            })
            .catch(function(error) {
                console.log('ServiceWorker registration failed:', error);
            });
    });
}
</script>

<style>
<?php echo LoadingManager::getLoadingCSS(); ?>
</style>

<!-- Floating Notification Container -->
<div id="floatingNotifications" class="floating-notifications"></div>

<style>
.notifications-container {
    position: relative;
}

.notification-bell {
    background: transparent;
    border: none;
    color: var(--text-secondary);
    font-size: 1.2rem;
    cursor: pointer;
    padding: 10px;
    border-radius: 50%;
    transition: all 0.3s ease;
    position: relative;
}

.notification-bell:hover {
    color: var(--primary);
    background: rgba(0, 245, 255, 0.1);
}

.notification-bell.has-unread::after {
    content: '';
    position: absolute;
    top: 5px;
    right: 5px;
    width: 10px;
    height: 10px;
    background: var(--accent);
    border-radius: 50%;
    box-shadow: 0 0 0 2px var(--dark-bg);
}

.notification-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--accent);
    color: white;
    font-size: 0.7rem;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}

.notifications-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 350px;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    display: none;
}

.notifications-container:hover .notifications-dropdown {
    display: block;
}

.notifications-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-color);
}

.notifications-header h3 {
    margin: 0;
    color: var(--text-primary);
    font-size: 1.1rem;
}

.mark-all-read {
    background: transparent;
    border: none;
    color: var(--primary);
    font-size: 0.9rem;
    cursor: pointer;
    padding: 5px 10px;
    border-radius: 5px;
    transition: all 0.2s ease;
}

.mark-all-read:hover {
    background: rgba(0, 245, 255, 0.1);
}

.notifications-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-color);
    transition: all 0.2s ease;
    cursor: pointer;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item:hover {
    background: rgba(0, 245, 255, 0.05);
}

.notification-icon {
    background: rgba(0, 245, 255, 0.1);
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-content h4 {
    margin: 0 0 5px 0;
    font-size: 0.95rem;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.notification-content p {
    margin: 0 0 5px 0;
    font-size: 0.85rem;
    color: var(--text-secondary);
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.notification-content small {
    color: var(--text-secondary);
    font-size: 0.75rem;
}

.mark-read {
    background: transparent;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    opacity: 0;
    transition: all 0.2s ease;
}

.notification-item:hover .mark-read {
    opacity: 1;
}

.mark-read:hover {
    color: var(--primary);
    background: rgba(0, 245, 255, 0.1);
}

.no-notifications {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-secondary);
}

.no-notifications i {
    font-size: 2rem;
    margin-bottom: 15px;
    display: block;
}

.notifications-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--border-color);
    text-align: center;
}

.notifications-footer a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
}

.notifications-footer a:hover {
    color: var(--secondary);
}

/* Floating Notifications */
.floating-notifications {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    width: 350px;
}

.floating-notification {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    transform: translateX(400px);
    animation: slideInRight 0.5s ease forwards;
    position: relative;
    overflow: hidden;
}

.floating-notification.closing {
    animation: slideOutRight 0.5s ease forwards;
}

@keyframes slideInRight {
    from { transform: translateX(400px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOutRight {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(400px); opacity: 0; }
}

.floating-notification::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    height: 3px;
    width: 100%;
    background: linear-gradient(90deg, var(--primary), var(--secondary));
    animation: progress 8s linear forwards;
}

@keyframes progress {
    from { width: 100%; }
    to { width: 0%; }
}

.floating-notification.success::before { background: var(--success); }
.floating-notification.error::before { background: var(--danger); }
.floating-notification.warning::before { background: var(--warning); }
.floating-notification.info::before { background: var(--primary); }

.floating-notification-content {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.floating-notification-icon {
    font-size: 1.2rem;
    margin-top: 3px;
}

.floating-notification.success .floating-notification-icon { color: var(--success); }
.floating-notification.error .floating-notification-icon { color: var(--danger); }
.floating-notification.warning .floating-notification-icon { color: var(--warning); }
.floating-notification.info .floating-notification-icon { color: var(--primary); }

.floating-notification-text h4 {
    margin: 0 0 5px 0;
    font-size: 1rem;
    color: var(--text-primary);
}

.floating-notification-text p {
    margin: 0;
    font-size: 0.9rem;
    color: var(--text-secondary);
    line-height: 1.4;
}

.close-notification {
    position: absolute;
    top: 10px;
    right: 10px;
    background: transparent;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    font-size: 1.1rem;
    padding: 5px;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.close-notification:hover {
    color: var(--text-primary);
    background: rgba(255, 255, 255, 0.1);
}
</style>

<script src="assets/js/main.js"></script>
<script src="assets/js/mobile-enhancements.js"></script>

<script>
// Handle guest users trying to post
function handleGuestPost() {
    if (confirm('You need to register an account to post. Would you like to register now?')) {
        window.location.href = 'register.php';
    }
}

// Time ago function
function time_ago(time) {
    const date = new Date(time);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
    if (diff < 2592000) return Math.floor(diff / 86400) + ' days ago';
    return date.toLocaleDateString();
}

// Notification System
document.addEventListener('DOMContentLoaded', function() {
    // Mark notification as read
    document.querySelectorAll('.mark-read').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const notificationId = this.dataset.notificationId;
            markNotificationAsRead(notificationId);
        });
    });
    
    // Mark all as read
    const markAllReadBtn = document.getElementById('markAllRead');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            markAllNotificationsAsRead();
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.notifications-container')) {
            const dropdown = document.getElementById('notificationsDropdown');
            if (dropdown) {
                dropdown.style.display = 'none';
            }
        }
    });
});

function markNotificationAsRead(notificationId) {
    fetch('ajax/mark-notification-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the notification from dropdown
            const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationItem) {
                notificationItem.style.opacity = '0';
                setTimeout(() => {
                    notificationItem.remove();
                    updateNotificationCount();
                }, 300);
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function markAllNotificationsAsRead() {
    fetch('ajax/mark-all-notifications-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove all notifications from dropdown
            document.querySelectorAll('.notification-item').forEach(item => {
                item.style.opacity = '0';
            });
            setTimeout(() => {
                document.querySelector('.notifications-list').innerHTML = `
                    <div class="no-notifications">
                        <i class="fas fa-inbox"></i>
                        <p>No new notifications</p>
                    </div>
                `;
                updateNotificationCount();
            }, 300);
        }
    })
    .catch(error => console.error('Error:', error));
}

function updateNotificationCount() {
    fetch('ajax/get-notification-count.php')
    .then(response => response.json())
    .then(data => {
        const bell = document.querySelector('.notification-bell');
        const countSpan = document.querySelector('.notification-count');
        
        if (data.count > 0) {
            bell.classList.add('has-unread');
            if (countSpan) {
                countSpan.textContent = Math.min(data.count, 99);
            } else {
                const newCountSpan = document.createElement('span');
                newCountSpan.className = 'notification-count';
                newCountSpan.textContent = Math.min(data.count, 99);
                bell.appendChild(newCountSpan);
            }
        } else {
            bell.classList.remove('has-unread');
            if (countSpan) {
                countSpan.remove();
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// Show floating notification
function showFloatingNotification(title, message, type = 'info') {
    const container = document.getElementById('floatingNotifications');
    const notification = document.createElement('div');
    notification.className = `floating-notification ${type}`;
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    notification.innerHTML = `
        <div class="floating-notification-content">
            <div class="floating-notification-icon">
                <i class="fas ${icons[type] || icons.info}"></i>
            </div>
            <div class="floating-notification-text">
                <h4>${title}</h4>
                <p>${message}</p>
            </div>
            <button class="close-notification">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Add close functionality
    const closeBtn = notification.querySelector('.close-notification');
    closeBtn.addEventListener('click', function() {
        closeFloatingNotification(notification);
    });
    
    // Auto-close after 8 seconds
    setTimeout(() => {
        closeFloatingNotification(notification);
    }, 8000);
}

function closeFloatingNotification(notification) {
    notification.classList.add('closing');
    setTimeout(() => {
        notification.remove();
    }, 500);
}

// Example usage: showFloatingNotification('Success!', 'Your post has been published.', 'success');
</script>