<?php
require_once 'cookie-manager.php';
?>

<footer class="cyber-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3><i class="fas fa-robot"></i> FUROM <span class="version-tag">V5.1</span></h3>
                <p>The next-generation community platform built for the future.</p>
                <div class="upgrade-notice">
                    <small>✨ Enhanced with smart caching and loading systems!</small>
                </div>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="about.php">About</a></li>
                    <li><a href="rules.php">Community Rules</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="changelog.php">What's New</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Connect</h4>
                <div class="social-links">
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-discord"></i></a>
                    <a href="#"><i class="fab fa-github"></i></a>
                </div>
                <div class="theme-toggle" style="margin-top: 1rem;">
                    <button id="themeToggle" class="btn btn-sm btn-outline">
                        <i class="fas fa-moon"></i> Toggle Theme
                    </button>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Furom. All rights reserved.</p>
            <div class="footer-stats">
                <span>Page generated in <?php echo round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4); ?>s</span>
                <span>•</span>
                <span><?php echo number_format(memory_get_peak_usage(true) / 1024 / 1024, 2); ?> MB</span>
            </div>
        </div>
    </div>
</footer>

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