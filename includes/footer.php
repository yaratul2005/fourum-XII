<?php
require_once 'cookie-manager.php';

// Get admin-controlled footer settings
$footer_text = get_setting('footer_text', '© 2024 Furom. All rights reserved.');
$footer_custom_html = get_setting('footer_custom_html', '');
$custom_footer_html = get_site_appearance('custom_footer_html', '');
?>

<footer class="cyber-footer">
    <div class="container">
        <div class="footer-content">
            <?php if (!empty($custom_footer_html)): ?>
                <div class="custom-footer-content">
                    <?php echo $custom_footer_html; ?>
                </div>
            <?php endif; ?>
            
            <div class="footer-info">
                <p><?php echo $footer_text; ?></p>
                <div class="footer-links">
                    <a href="about.php">About</a>
                    <a href="privacy.php">Privacy</a>
                    <a href="terms.php">Terms</a>
                    <a href="contact.php">Contact</a>
                </div>
            </div>
            
            <?php if (!empty($footer_custom_html)): ?>
                <div class="admin-footer-content">
                    <?php echo $footer_custom_html; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</footer>

<style>
.footer-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    text-align: center;
}

.footer-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.footer-links {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    justify-content: center;
}

.footer-links a {
    color: var(--text-secondary);
    text-decoration: none;
    transition: all 0.3s ease;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

.footer-links a:hover {
    color: var(--primary);
    background: rgba(0, 245, 255, 0.1);
}

.custom-footer-content,
.admin-footer-content {
    width: 100%;
    text-align: center;
}
</style>

<!-- Cookie Consent Banner -->
<?php echo CookieManager::getConsentBanner(); ?>

<script>
// Theme toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.body.classList.contains('light-theme') ? 'light' : 'dark';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.body.classList.toggle('light-theme');
            document.body.classList.toggle('dark-theme');
            
            // Update cookie
            fetch('/ajax/update-preference.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({preference: 'theme', value: newTheme})
            });
            
            // Update button icon
            const icon = themeToggle.querySelector('i');
            icon.className = newTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
        });
    }
});

// Performance monitoring
if ('performance' in window) {
    window.addEventListener('load', function() {
        setTimeout(function() {
            const perfData = performance.getEntriesByType('navigation')[0];
            if (perfData) {
                console.log('Page Load Metrics:', {
                    'DNS Lookup': perfData.domainLookupEnd - perfData.domainLookupStart,
                    'TCP Connection': perfData.connectEnd - perfData.connectStart,
                    'Request Time': perfData.responseEnd - perfData.requestStart,
                    'DOM Processing': perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart,
                    'Total Load': perfData.loadEventEnd - perfData.loadEventStart
                });
            }
        }, 0);
    });
}

// Cache management utilities
window.furom.cache = {
    clearAll: function() {
        if ('caches' in window) {
            caches.keys().then(function(cacheNames) {
                return Promise.all(
                    cacheNames.map(function(cacheName) {
                        return caches.delete(cacheName);
                    })
                );
            }).then(function() {
                console.log('All caches cleared');
                if (navigator.serviceWorker.controller) {
                    navigator.serviceWorker.controller.postMessage({action: 'clearCache'});
                }
            });
        }
    },
    
    getStats: function() {
        if ('storage' in navigator && 'estimate' in navigator.storage) {
            navigator.storage.estimate().then(function(estimate) {
                console.log('Storage usage:', {
                    used: Math.round(estimate.usage / 1024 / 1024) + ' MB',
                    available: Math.round(estimate.quota / 1024 / 1024) + ' MB'
                });
            });
        }
    }
};
</script>