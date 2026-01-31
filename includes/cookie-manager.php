<?php
/**
 * Smart Cookie Manager for Furom
 * Handles user preferences, session persistence, and analytics
 */

class CookieManager {
    private static $defaults = [
        'theme' => 'dark',
        'language' => 'en',
        'notifications' => 'enabled',
        'auto_refresh' => 'disabled',
        'layout' => 'default'
    ];
    
    /**
     * Get cookie value with default fallback
     */
    public static function get($name, $default = null) {
        $default = $default ?? (self::$defaults[$name] ?? null);
        return $_COOKIE[$name] ?? $default;
    }
    
    /**
     * Set cookie with smart defaults
     */
    public static function set($name, $value, $expire_days = 30, $path = '/', $domain = null) {
        if ($domain === null) {
            $domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
        }
        
        $expire = time() + ($expire_days * 24 * 60 * 60);
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $httponly = true;
        
        return setcookie($name, $value, [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => 'Lax'
        ]);
    }
    
    /**
     * Delete cookie
     */
    public static function delete($name, $path = '/', $domain = null) {
        if ($domain === null) {
            $domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
        }
        
        return setcookie($name, '', [
            'expires' => time() - 3600,
            'path' => $path,
            'domain' => $domain,
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    /**
     * Get all user preferences
     */
    public static function getAllPreferences() {
        $preferences = [];
        foreach (self::$defaults as $key => $default) {
            $preferences[$key] = self::get($key, $default);
        }
        return $preferences;
    }
    
    /**
     * Set multiple preferences at once
     */
    public static function setPreferences($preferences) {
        foreach ($preferences as $key => $value) {
            self::set($key, $value);
        }
    }
    
    /**
     * Initialize user preferences
     */
    public static function initializePreferences() {
        // Set default preferences for new users
        foreach (self::$defaults as $key => $default) {
            if (!isset($_COOKIE[$key])) {
                self::set($key, $default);
            }
        }
    }
    
    /**
     * Get analytics cookie data
     */
    public static function getAnalyticsData() {
        return [
            'first_visit' => self::get('first_visit', date('Y-m-d')),
            'last_visit' => self::get('last_visit', date('Y-m-d H:i:s')),
            'visit_count' => (int)self::get('visit_count', 1),
            'preferred_theme' => self::get('theme'),
            'language' => self::get('language')
        ];
    }
    
    /**
     * Update analytics data
     */
    public static function updateAnalytics() {
        // Update visit count
        $visit_count = (int)self::get('visit_count', 0) + 1;
        self::set('visit_count', $visit_count);
        
        // Update last visit
        self::set('last_visit', date('Y-m-d H:i:s'));
        
        // Set first visit if not exists
        if (!self::get('first_visit')) {
            self::set('first_visit', date('Y-m-d'));
        }
    }
    
    /**
     * Generate consent cookie banner HTML
     */
    public static function getConsentBanner() {
        if (self::get('cookie_consent', 'false') === 'true') {
            return '';
        }
        
        return "
        <div id='cookie-consent-banner' class='cookie-banner'>
            <div class='cookie-content'>
                <p>We use cookies to enhance your experience and analyze site usage. 
                   By continuing to use our site, you agree to our use of cookies.</p>
                <div class='cookie-buttons'>
                    <button id='accept-cookies' class='btn btn-primary'>Accept All</button>
                    <button id='reject-cookies' class='btn btn-secondary'>Reject Non-Essential</button>
                </div>
            </div>
        </div>
        <style>
        .cookie-banner {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--card-bg);
            border-top: 1px solid var(--border-color);
            padding: 1rem;
            z-index: 10000;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }
        
        .cookie-banner.show {
            transform: translateY(0);
        }
        
        .cookie-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .cookie-content p {
            flex: 1;
            margin: 0;
            color: var(--text-primary);
        }
        
        .cookie-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .cookie-content {
                flex-direction: column;
                text-align: center;
            }
            
            .cookie-buttons {
                width: 100%;
                justify-content: center;
            }
        }
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const banner = document.getElementById('cookie-consent-banner');
            if (banner) {
                // Show banner after slight delay
                setTimeout(() => {
                    banner.classList.add('show');
                }, 1000);
                
                // Handle button clicks
                document.getElementById('accept-cookies').addEventListener('click', function() {
                    fetch('/ajax/set-cookie-consent.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({consent: 'accepted'})
                    }).then(() => {
                        banner.classList.remove('show');
                    });
                });
                
                document.getElementById('reject-cookies').addEventListener('click', function() {
                    fetch('/ajax/set-cookie-consent.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({consent: 'rejected'})
                    }).then(() => {
                        banner.classList.remove('show');
                    });
                });
            }
        });
        </script>
        ";
    }
}

// Initialize preferences on each page load
CookieManager::initializePreferences();
CookieManager::updateAnalytics();
?>