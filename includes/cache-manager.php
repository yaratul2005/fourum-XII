<?php
/**
 * Smart Cache Manager for Furom
 * Handles browser caching, asset optimization, and loading states
 * Defensive version that gracefully handles header issues
 */

class CacheManager {
    private static $instance = null;
    private $cache_dir;
    private $cache_lifetime = 3600; // 1 hour default
    
    private function __construct() {
        $this->cache_dir = dirname(__DIR__) . '/cache/';
        $this->ensureCacheDirectory();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function ensureCacheDirectory() {
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
        if (!is_writable($this->cache_dir)) {
            chmod($this->cache_dir, 0755);
        }
    }
    
    /**
     * Generate cache key based on content
     */
    public function generateCacheKey($content, $prefix = '') {
        return $prefix . md5($content);
    }
    
    /**
     * Store content in cache
     */
    public function setCache($key, $data, $lifetime = null) {
        $lifetime = $lifetime ?: $this->cache_lifetime;
        $cache_file = $this->cache_dir . $key . '.cache';
        $cache_data = [
            'expires' => time() + $lifetime,
            'data' => $data
        ];
        
        return file_put_contents($cache_file, serialize($cache_data)) !== false;
    }
    
    /**
     * Retrieve content from cache
     */
    public function getCache($key) {
        $cache_file = $this->cache_dir . $key . '.cache';
        
        if (!file_exists($cache_file)) {
            return false;
        }
        
        $cache_data = unserialize(file_get_contents($cache_file));
        
        if ($cache_data['expires'] < time()) {
            unlink($cache_file);
            return false;
        }
        
        return $cache_data['data'];
    }
    
    /**
     * Clear expired cache files
     */
    public function clearExpiredCache() {
        $files = glob($this->cache_dir . '*.cache');
        $cleared = 0;
        
        foreach ($files as $file) {
            $cache_data = unserialize(file_get_contents($file));
            if ($cache_data['expires'] < time()) {
                unlink($file);
                $cleared++;
            }
        }
        
        return $cleared;
    }
    
    /**
     * Set browser cache headers defensively
     */
    public static function setBrowserCache($max_age = 3600, $public = true) {
        // Multiple checks to prevent header errors
        if (headers_sent($file, $line)) {
            error_log("Headers already sent in $file at line $line - cannot set cache headers");
            return false;
        }
        
        if (ob_get_level() > 0) {
            // There's output buffering, but headers might still be sendable
            $contents = ob_get_contents();
            if ($contents !== '' && $contents !== false) {
                error_log("Output buffering contains content - headers may be problematic");
                // Still attempt to set headers but be prepared for failure
            }
        }
        
        try {
            $cache_control = $public ? 'public' : 'private';
            header("Cache-Control: {$cache_control}, max-age={$max_age}", true);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $max_age) . ' GMT', true);
            return true;
        } catch (Exception $e) {
            error_log("Failed to set cache headers: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Set no-cache headers defensively
     */
    public static function setNoCache() {
        // Multiple checks to prevent header errors
        if (headers_sent($file, $line)) {
            error_log("Headers already sent in $file at line $line - cannot set no-cache headers");
            return false;
        }
        
        if (ob_get_level() > 0) {
            $contents = ob_get_contents();
            if ($contents !== '' && $contents !== false) {
                error_log("Output buffering contains content - no-cache headers may be problematic");
            }
        }
        
        try {
            header('Cache-Control: no-cache, no-store, must-revalidate', true);
            header('Pragma: no-cache', true);
            header('Expires: 0', true);
            return true;
        } catch (Exception $e) {
            error_log("Failed to set no-cache headers: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Safe header setting with fallback
     */
    public static function safeHeader($header, $replace = true) {
        if (headers_sent($file, $line)) {
            error_log("Cannot set header '$header' - headers already sent in $file at line $line");
            return false;
        }
        
        try {
            header($header, $replace);
            return true;
        } catch (Exception $e) {
            error_log("Failed to set header '$header': " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Asset Manager for optimized loading
 */
class AssetManager {
    private static $loaded_assets = [];
    private static $inline_css = [];
    private static $inline_js = [];
    
    /**
     * Load CSS file with cache busting
     */
    public static function loadCSS($path, $async = false) {
        $cache_key = 'css_' . md5($path);
        if (isset(self::$loaded_assets[$cache_key])) {
            return;
        }
        
        $file_time = file_exists($path) ? filemtime($path) : time();
        $version = '?v=' . $file_time;
        
        if ($async) {
            echo "<link rel='preload' href='{$path}{$version}' as='style' onload='this.onload=null;this.rel=\"stylesheet\"'>\n";
            echo "<noscript><link rel='stylesheet' href='{$path}{$version}'></noscript>\n";
        } else {
            echo "<link rel='stylesheet' href='{$path}{$version}'>\n";
        }
        
        self::$loaded_assets[$cache_key] = true;
    }
    
    /**
     * Load JS file with async/defer options
     */
    public static function loadJS($path, $async = false, $defer = false) {
        $cache_key = 'js_' . md5($path);
        if (isset(self::$loaded_assets[$cache_key])) {
            return;
        }
        
        $file_time = file_exists($path) ? filemtime($path) : time();
        $version = '?v=' . $file_time;
        $attributes = [];
        
        if ($async) $attributes[] = 'async';
        if ($defer) $attributes[] = 'defer';
        
        $attr_string = !empty($attributes) ? ' ' . implode(' ', $attributes) : '';
        
        echo "<script src='{$path}{$version}'{$attr_string}></script>\n";
        self::$loaded_assets[$cache_key] = true;
    }
    
    /**
     * Add inline CSS
     */
    public static function addInlineCSS($css) {
        self::$inline_css[] = $css;
    }
    
    /**
     * Add inline JS
     */
    public static function addInlineJS($js) {
        self::$inline_js[] = $js;
    }
    
    /**
     * Output collected inline CSS
     */
    public static function outputInlineCSS() {
        if (!empty(self::$inline_css)) {
            echo "<style>\n" . implode("\n", self::$inline_css) . "\n</style>\n";
        }
    }
    
    /**
     * Output collected inline JS
     */
    public static function outputInlineJS() {
        if (!empty(self::$inline_js)) {
            echo "<script>\n" . implode("\n", self::$inline_js) . "\n</script>\n";
        }
    }
}

/**
 * Loading State Manager
 */
class LoadingManager {
    private static $loading_states = [];
    
    /**
     * Register a loading state
     */
    public static function registerLoadingState($id, $message = 'Loading...', $type = 'spinner') {
        self::$loading_states[$id] = [
            'message' => $message,
            'type' => $type
        ];
    }
    
    /**
     * Generate loading HTML
     */
    public static function generateLoadingHTML($id) {
        if (!isset(self::$loading_states[$id])) {
            return '';
        }
        
        $state = self::$loading_states[$id];
        $html = "<div class='loading-state' id='loading-{$id}' style='display:none;'>";
        
        switch ($state['type']) {
            case 'spinner':
                $html .= "<div class='loading-spinner'></div>";
                break;
            case 'dots':
                $html .= "<div class='loading-dots'><span></span><span></span><span></span></div>";
                break;
            case 'bar':
                $html .= "<div class='loading-bar'><div class='loading-progress'></div></div>";
                break;
        }
        
        $html .= "<div class='loading-message'>{$state['message']}</div>";
        $html .= "</div>";
        
        return $html;
    }
    
    /**
     * Get loading CSS
     */
    public static function getLoadingCSS() {
        return "
        .loading-state {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            color: white;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(0, 245, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
            margin-bottom: 20px;
        }
        
        .loading-dots {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
        }
        
        .loading-dots span {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
            animation: bounce 1.5s infinite ease-in-out;
        }
        
        .loading-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .loading-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        .loading-bar {
            width: 200px;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .loading-progress {
            height: 100%;
            width: 0%;
            background: var(--primary);
            border-radius: 2px;
            animation: loadingProgress 2s infinite;
        }
        
        .loading-message {
            font-family: 'Orbitron', monospace;
            font-size: 1.2rem;
            text-align: center;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        
        @keyframes loadingProgress {
            0% { width: 0%; }
            100% { width: 100%; }
        }
        ";
    }
}
?>