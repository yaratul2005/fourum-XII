<?php
require_once 'cookie-manager.php';

// Get admin-controlled footer settings
$footer_text = get_setting('footer_text', '© 2026 Furom. All rights reserved.');
$footer_custom_html = get_setting('footer_custom_html', '');
$custom_footer_html = get_site_appearance('custom_footer_html', '');
?>

<footer class="cyber-footer">
    <div class="container">
        <div class="footer-content">
            <!-- Quick Links Section -->
            <div class="footer-section footer-quick-links">
                <h3><i class="fas fa-link"></i> Quick Links</h3>
                <ul>
                    <li><a href="about.php">About</a></li>
                    <li><a href="community-rules.php">Community Rules</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="whats-new.php">What's New (V2)</a></li>
                </ul>
            </div>
            
            <!-- Connect Section -->
            <div class="footer-section footer-connect">
                <h3><i class="fas fa-plug"></i> Connect</h3>
                <div class="social-links">
                    <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" title="Discord"><i class="fab fa-discord"></i></a>
                    <a href="#" title="GitHub"><i class="fab fa-github"></i></a>
                </div>
            </div>
            
            <!-- Copyright Section -->
            <div class="footer-section">
                <p class="footer-bottom">
                    <?php echo $footer_text; ?>
                </p>
            </div>
        </div>
    </div>
</footer>

<style>
/* Footer-specific styling */
.footer-quick-links h3, .footer-connect h3 {
    margin-bottom: 1.2rem;
    font-size: 1.2rem;
    color: var(--primary);
}

.footer-quick-links ul {
    list-style: none;
    padding: 0;
}

.footer-quick-links li {
    margin-bottom: 0.8rem;
}

.footer-quick-links a {
    color: var(--text-secondary);
    text-decoration: none;
    display: block;
    padding: 0.5rem 0;
    transition: all 0.2s ease;
}

.footer-quick-links a:hover {
    color: var(--primary);
    padding-left: 5px;
}

.footer-connect h3 {
    margin-bottom: 1.2rem;
    font-size: 1.2rem;
    color: var(--primary);
}

.social-links {
    display: flex;
    gap: 1.25rem;
    justify-content: center;
}

.social-links a {
    color: var(--text-secondary);
    font-size: 1.75rem;
    transition: all 0.3s ease;
    position: relative;
}

.social-links a:hover {
    color: var(--primary);
    transform: translateY(-3px) scale(1.1);
}

.social-links a::before {
    content: '';
    position: absolute;
    top: -5px;
    left: -5px;
    right: -5px;
    bottom: -5px;
    border: 1px solid transparent;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.social-links a:hover::before {
    border-color: var(--primary);
    box-shadow: 0 0 15px var(--primary);
}

/* Responsive design for mobile */
@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        gap: 2rem;
        padding: 2rem 1rem;
    }
    
    .footer-section {
        width: 100%;
        text-align: center;
    }
    
    .footer-quick-links ul {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .footer-quick-links li {
        margin: 0.5rem 0;
    }
    
    .social-links {
        justify-content: center;
    }
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
</script>