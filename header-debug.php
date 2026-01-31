<?php
/**
 * Header Debug Script
 * Helps diagnose header-related issues
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Header Debug Information</h1>\n";

// Check current headers status
echo "<h2>Current Headers Status</h2>\n";
echo "<p>Headers sent: " . (headers_sent() ? 'YES' : 'NO') . "</p>\n";

if (headers_sent($filename, $linenum)) {
    echo "<p>Headers sent in file: $filename at line: $linenum</p>\n";
}

// Check output buffering
echo "<h2>Output Buffering Status</h2>\n";
echo "<p>Output buffering level: " . ob_get_level() . "</p>\n";

// Check session status
echo "<h2>Session Status</h2>\n";
$session_status = session_status();
switch ($session_status) {
    case PHP_SESSION_DISABLED:
        echo "<p>Session: DISABLED</p>\n";
        break;
    case PHP_SESSION_NONE:
        echo "<p>Session: NONE (not started)</p>\n";
        break;
    case PHP_SESSION_ACTIVE:
        echo "<p>Session: ACTIVE</p>\n";
        break;
}

// Test cache header setting
echo "<h2>Cache Header Test</h2>\n";
if (!headers_sent()) {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "<p style='color: green;'>✓ Cache headers set successfully</p>\n";
} else {
    echo "<p style='color: red;'>✗ Cannot set headers - already sent</p>\n";
}

// Check for BOM or whitespace
echo "<h2>File Analysis</h2>\n";
$files_to_check = [
    'config.php',
    'includes/functions.php',
    'includes/cache-manager.php',
    'includes/header.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $has_bom = (substr($content, 0, 3) === "\xEF\xBB\xBF");
        $starts_with_whitespace = preg_match('/^\s+/', $content);
        
        echo "<p><strong>$file:</strong> ";
        if ($has_bom) {
            echo "<span style='color: red;'>Has BOM</span> ";
        }
        if ($starts_with_whitespace) {
            echo "<span style='color: orange;'>Starts with whitespace</span> ";
        }
        if (!$has_bom && !$starts_with_whitespace) {
            echo "<span style='color: green;'>Clean</span>";
        }
        echo "</p>\n";
    }
}

// Test including files
echo "<h2>Inclusion Test</h2>\n";
try {
    require_once 'config.php';
    echo "<p style='color: green;'>✓ config.php loaded successfully</p>\n";
    
    require_once 'includes/functions.php';
    echo "<p style='color: green;'>✓ functions.php loaded successfully</p>\n";
    
    require_once 'includes/cache-manager.php';
    echo "<p style='color: green;'>✓ cache-manager.php loaded successfully</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error loading files: " . $e->getMessage() . "</p>\n";
}

echo "<h2>Current GET Parameters</h2>\n";
echo "<pre>" . print_r($_GET, true) . "</pre>\n";

echo "<h2>Current POST Parameters</h2>\n";
echo "<pre>" . print_r($_POST, true) . "</pre>\n";

echo "<h2>Current SESSION Data</h2>\n";
echo "<pre>" . print_r($_SESSION ?? [], true) . "</pre>\n";

echo "<h2>Server Information</h2>\n";
echo "<p>PHP Version: " . phpversion() . "</p>\n";
echo "<p>Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>\n";
echo "<p>Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>\n";
?>