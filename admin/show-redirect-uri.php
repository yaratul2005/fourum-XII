<?php
// Simple Redirect URI Display Tool
// This tool will always show the correct redirect URI regardless of other system issues

// Set the correct redirect URI for your installation
$correct_redirect_uri = 'https://great10.xyz/auth/google/callback.php';

// Get additional information for troubleshooting
$server_info = [
    'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'Not available',
    'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? 'Not available',
    'HTTPS' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'Yes' : 'No',
    'REQUEST_SCHEME' => $_SERVER['REQUEST_SCHEME'] ?? 'Not available'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google OAuth Redirect URI</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .uri-display { 
            background: linear-gradient(135deg, #1e293b, #0f172a); 
            padding: 30px; 
            margin: 20px 0; 
            border-radius: 12px; 
            border: 2px solid #334155;
            text-align: center;
        }
        .uri-input { 
            width: 100%; 
            padding: 15px; 
            background: #0b1120; 
            color: #00ff9d; 
            border: 2px solid #00ff9d; 
            border-radius: 8px; 
            font-size: 1.2em; 
            font-weight: bold;
            text-align: center;
            margin: 15px 0;
        }
        .info-box { 
            background: rgba(59, 130, 246, 0.15); 
            border: 1px solid rgba(59, 130, 246, 0.3); 
            padding: 15px; 
            border-radius: 8px; 
            margin: 15px 0;
        }
        .server-info { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 10px; 
            margin-top: 20px;
        }
        .server-item { 
            background: rgba(255,255,255,0.05); 
            padding: 10px; 
            border-radius: 5px;
        }
        .server-label { 
            font-weight: bold; 
            color: #00f5ff; 
        }
        .server-value { 
            color: #a0a0c0; 
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-link"></i> TOOLS</h2>
                <span>V4.0</span>
            </div>
            <nav class="sidebar-nav">
                <a href="show-redirect-uri.php" class="nav-item active">
                    <i class="fas fa-link"></i> Show Redirect URI
                </a>
                <a href="google-auth-settings.php" class="nav-item">
                    <i class="fab fa-google"></i> Google Auth Settings
                </a>
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="../index.php" class="nav-item">
                    <i class="fas fa-arrow-left"></i> Back to Site
                </a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <h1><i class="fas fa-link"></i> Google OAuth Redirect URI</h1>
                <p>Your correct redirect URI for Google OAuth configuration</p>
            </header>

            <div class="admin-content">
                <div class="uri-display">
                    <h2><i class="fab fa-google"></i> Google OAuth Redirect URI</h2>
                    <p>Copy this URI to your Google Cloud Console OAuth credentials:</p>
                    <input type="text" id="redirectUri" class="uri-input" value="<?php echo htmlspecialchars($correct_redirect_uri); ?>" readonly>
                    <button onclick="copyURI()" class="btn btn-primary" id="copyBtn">
                        <i class="fas fa-copy"></i> Copy to Clipboard
                    </button>
                </div>

                <div class="info-box">
                    <h3><i class="fas fa-info-circle"></i> Instructions</h3>
                    <ol>
                        <li>Copy the redirect URI above</li>
                        <li>Go to <a href="https://console.cloud.google.com/" target="_blank" style="color: #00f5ff;">Google Cloud Console</a></li>
                        <li>Navigate to "APIs & Services" → "Credentials"</li>
                        <li>Select your OAuth 2.0 Client ID</li>
                        <li>In the "Authorized redirect URIs" section, paste the copied URI</li>
                        <li>Click "Save"</li>
                    </ol>
                </div>

                <div class="config-section">
                    <h2><i class="fas fa-server"></i> Server Information</h2>
                    <p>Current server configuration details:</p>
                    <div class="server-info">
                        <?php foreach ($server_info as $key => $value): ?>
                            <div class="server-item">
                                <span class="server-label"><?php echo str_replace('_', ' ', $key); ?>:</span>
                                <span class="server-value"><?php echo htmlspecialchars($value); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="config-section">
                    <h2><i class="fas fa-check-circle"></i> Verification</h2>
                    <p>To verify this is correct for your installation:</p>
                    <ul>
                        <li>Your domain: <strong>great10.xyz</strong></li>
                        <li>Callback path: <strong>/auth/google/callback.php</strong></li>
                        <li>Full URI: <strong>https://great10.xyz/auth/google/callback.php</strong></li>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <script>
        function copyURI() {
            const uriInput = document.getElementById('redirectUri');
            const copyBtn = document.getElementById('copyBtn');
            
            // Select and copy
            uriInput.select();
            document.execCommand('copy');
            
            // Visual feedback
            const originalText = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            copyBtn.classList.add('btn-success');
            
            // Reset after 2 seconds
            setTimeout(() => {
                copyBtn.innerHTML = originalText;
                copyBtn.classList.remove('btn-success');
            }, 2000);
        }
    </script>
</body>
</html>