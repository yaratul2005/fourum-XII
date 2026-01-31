<?php
// Furom Diagnostic Tool
error_reporting(E_ALL);
ini_set('display_errors', 1);

$results = [];

// Check PHP version
$results['PHP Version'] = [
    'value' => PHP_VERSION,
    'status' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'PASS' : 'FAIL',
    'message' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'Supported' : 'PHP 7.4+ required'
];

// Check extensions
$extensions = ['pdo', 'pdo_mysql', 'mysqli', 'gd', 'openssl', 'curl', 'json'];
foreach ($extensions as $ext) {
    $results["Extension: $ext"] = [
        'value' => extension_loaded($ext) ? 'Loaded' : 'Not Loaded',
        'status' => extension_loaded($ext) ? 'PASS' : 'FAIL',
        'message' => extension_loaded($ext) ? 'Available' : 'Required for full functionality'
    ];
}

// Check file permissions
$files_to_check = [
    'config.php',
    'includes/functions.php',
    'assets/css/style.css',
    'assets/js/main.js'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        $writable = is_writable($file);
        $results["File: $file"] = [
            'value' => "Permissions: $perms, Writable: " . ($writable ? 'Yes' : 'No'),
            'status' => $writable ? 'PASS' : 'WARNING',
            'message' => $writable ? 'Good permissions' : 'May cause issues if write access needed'
        ];
    } else {
        $results["File: $file"] = [
            'value' => 'File not found',
            'status' => 'FAIL',
            'message' => 'Required file missing'
        ];
    }
}

// Check directory permissions
$dirs_to_check = ['ajax', 'database', 'includes', 'assets'];
foreach ($dirs_to_check as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir);
        $results["Directory: $dir"] = [
            'value' => "Permissions: $perms, Writable: " . ($writable ? 'Yes' : 'No'),
            'status' => $writable ? 'PASS' : 'WARNING',
            'message' => $writable ? 'Good permissions' : 'May cause issues'
        ];
    } else {
        $results["Directory: $dir"] = [
            'value' => 'Directory not found',
            'status' => 'FAIL',
            'message' => 'Required directory missing'
        ];
    }
}

// Database connection test
if (file_exists('config.php')) {
    try {
        include 'config.php';
        if (isset($pdo)) {
            $results['Database Connection'] = [
                'value' => 'Connected successfully',
                'status' => 'PASS',
                'message' => 'Database connection working'
            ];
        } else {
            $results['Database Connection'] = [
                'value' => 'Config loaded but no PDO connection',
                'status' => 'WARNING',
                'message' => 'Check database configuration'
            ];
        }
    } catch (Exception $e) {
        $results['Database Connection'] = [
            'value' => 'Connection failed: ' . $e->getMessage(),
            'status' => 'FAIL',
            'message' => 'Database configuration issue'
        ];
    }
} else {
    $results['Database Connection'] = [
        'value' => 'config.php not found',
        'status' => 'FAIL',
        'message' => 'Run installation first'
    ];
}

// Server information
$results['Server Software'] = [
    'value' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'status' => 'INFO',
    'message' => 'Web server information'
];

$results['Document Root'] = [
    'value' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'status' => 'INFO',
    'message' => 'Web root directory'
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Furom Diagnostic Report</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .result-item {
            background: white;
            margin: 10px 0;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .status-pass { border-left: 4px solid #28a745; }
        .status-fail { border-left: 4px solid #dc3545; }
        .status-warning { border-left: 4px solid #ffc107; }
        .status-info { border-left: 4px solid #17a2b8; }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
        }
        .badge-pass { background: #28a745; color: white; }
        .badge-fail { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: black; }
        .badge-info { background: #17a2b8; color: white; }
        .solution {
            background: #e9f7fe;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-top: 10px;
            border-radius: 0 8px 8px 0;
        }
        .quick-fixes {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔧 Furom Diagnostic Report</h1>
        <p>System Health Check and Troubleshooting Guide</p>
    </div>

    <?php foreach ($results as $test => $result): ?>
        <div class="result-item status-<?php echo strtolower($result['status']); ?>">
            <div>
                <strong><?php echo htmlspecialchars($test); ?></strong>
                <br>
                <small><?php echo htmlspecialchars($result['value']); ?></small>
                <br>
                <em><?php echo htmlspecialchars($result['message']); ?></em>
            </div>
            <span class="status-badge badge-<?php echo strtolower($result['status']); ?>">
                <?php echo $result['status']; ?>
            </span>
        </div>
    <?php endforeach; ?>

    <div class="quick-fixes">
        <h3>⚡ Quick Fixes for Common Issues:</h3>
        <ol>
            <li><strong>500 Errors:</strong> Check file permissions (should be 644 for files, 755 for directories)</li>
            <li><strong>Database Issues:</strong> Verify database credentials in config.php</li>
            <li><strong>Missing Extensions:</strong> Contact your host to enable required PHP extensions</li>
            <li><strong>Parse Errors:</strong> Check PHP syntax in config files</li>
            <li><strong>Permission Denied:</strong> Make sure web server can read all files</li>
        </ol>
    </div>

    <div class="solution">
        <h3>📋 Next Steps:</h3>
        <ul>
            <li>Fix any FAIL items first</li>
            <li>Address WARNING items for optimal performance</li>
            <li>Run the installation wizard if config.php doesn't exist</li>
            <li>Check your server's error logs for specific error messages</li>
        </ul>
    </div>

    <div style="text-align: center; margin-top: 2rem; padding: 1rem; background: white; border-radius: 8px;">
        <a href="install.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">Run Installation</a>
        <a href="index.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">Try Homepage</a>
    </div>
</body>
</html>