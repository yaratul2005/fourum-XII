<?php
// Session and Header Test Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Session and Header Test</h1>\n";

// Test 1: Check current session status
echo "<h2>Test 1: Session Status</h2>\n";
echo "Session status: " . session_status() . "<br>\n";
echo "Session ID: " . (session_id() ?: 'None') . "<br>\n";

// Test 2: Try to start session
echo "<h2>Test 2: Session Start Attempt</h2>\n";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    echo "Session started successfully<br>\n";
} else {
    echo "Session already active<br>\n";
}

// Test 3: Check if headers have been sent
echo "<h2>Test 3: Headers Status</h2>\n";
if (headers_sent($file, $line)) {
    echo "Headers already sent in file: $file at line: $line<br>\n";
} else {
    echo "Headers not yet sent<br>\n";
}

// Test 4: Try to set a header
echo "<h2>Test 4: Header Setting Test</h2>\n";
if (!headers_sent()) {
    header('X-Test-Header: success');
    echo "Header set successfully<br>\n";
} else {
    echo "Cannot set header - headers already sent<br>\n";
}

// Test 5: Include config and test
echo "<h2>Test 5: Config Include Test</h2>\n";
try {
    require_once 'config.php';
    echo "Config included successfully<br>\n";
    echo "Session status after config: " . session_status() . "<br>\n";
    echo "Session ID after config: " . session_id() . "<br>\n";
    
    if (!headers_sent()) {
        header('X-Config-Test: success');
        echo "Header set after config successfully<br>\n";
    } else {
        echo "Cannot set header after config - headers already sent<br>\n";
    }
} catch (Exception $e) {
    echo "Error including config: " . $e->getMessage() . "<br>\n";
}

// Test 6: Test Google auth flow simulation
echo "<h2>Test 6: Google Auth Flow Simulation</h2>\n";
try {
    // Simulate the Google login flow
    $_SESSION['test_redirect'] = '/';
    
    if (!headers_sent()) {
        header('X-Google-Auth-Test: redirect-would-happen-here');
        echo "Google auth redirect simulation successful<br>\n";
    } else {
        echo "Google auth redirect would fail - headers already sent<br>\n";
    }
} catch (Exception $e) {
    echo "Google auth simulation error: " . $e->getMessage() . "<br>\n";
}

echo "<h2>Test Complete</h2>\n";
echo "<p>If all tests show success, the session and header issues should be resolved.</p>\n";
?>