<?php
// CSS Debugging Tool for Profile Edit Page
require_once 'config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

$user_id = get_current_user_id();
$user_data = get_user_data($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Debug Tool - Profile Edit</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .debug-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .debug-section {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .debug-header {
            margin-top: 0;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--primary);
            font-family: 'Orbitron', monospace;
        }
        
        .element-inspector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .inspector-card {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 1rem;
        }
        
        .inspector-card h4 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .css-property {
            display: flex;
            justify-content: space-between;
            padding: 0.25rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .css-property:last-child {
            border-bottom: none;
        }
        
        .property-name {
            color: var(--text-secondary);
            font-family: monospace;
        }
        
        .property-value {
            color: var(--success);
            font-family: monospace;
        }
        
        .conflict-detector {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid var(--danger);
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .fix-suggestion {
            background: rgba(0, 255, 157, 0.1);
            border: 1px solid var(--success);
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .avatar-test-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            background: var(--card-bg);
            border-radius: 15px;
            border: 1px solid var(--border-color);
            margin: 1rem 0;
        }
        
        .test-avatar-container {
            position: relative;
            margin-bottom: 1rem;
            cursor: pointer;
        }
        
        .test-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .test-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px var(--primary);
        }
        
        .test-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            color: white;
            font-size: 0.9rem;
            text-align: center;
        }
        
        .test-avatar-container:hover .test-overlay {
            opacity: 1;
        }
        
        .event-log {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 1rem;
            height: 200px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        .log-entry {
            margin: 0.25rem 0;
            padding: 0.25rem;
            border-radius: 3px;
        }
        
        .log-info {
            color: var(--primary);
        }
        
        .log-success {
            color: var(--success);
            background: rgba(16, 185, 129, 0.1);
        }
        
        .log-error {
            color: var(--danger);
            background: rgba(239, 68, 68, 0.1);
        }
        
        .log-warning {
            color: var(--warning);
            background: rgba(245, 158, 11, 0.1);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-container">
        <div class="container">
            <div class="debug-container">
                <div class="debug-section">
                    <h1 class="debug-header"><i class="fas fa-bug"></i> CSS Debugging Tool</h1>
                    <p>Diagnosing avatar upload and CSS issues on profile edit page</p>
                </div>
                
                <!-- Element Inspector -->
                <div class="debug-section">
                    <h2 class="debug-header"><i class="fas fa-search"></i> Element Inspector</h2>
                    <div class="element-inspector">
                        <div class="inspector-card">
                            <h4>Avatar Container (.avatar-preview-container)</h4>
                            <div class="css-property">
                                <span class="property-name">position:</span>
                                <span class="property-value" id="container-position">relative</span>
                            </div>
                            <div class="css-property">
                                <span class="property-name">cursor:</span>
                                <span class="property-value" id="container-cursor">pointer</span>
                            </div>
                            <div class="css-property">
                                <span class="property-name">z-index:</span>
                                <span class="property-value" id="container-zindex">auto</span>
                            </div>
                            <div class="css-property">
                                <span class="property-name">pointer-events:</span>
                                <span class="property-value" id="container-pointer">auto</span>
                            </div>
                        </div>
                        
                        <div class="inspector-card">
                            <h4>Avatar Image (.avatar-preview)</h4>
                            <div class="css-property">
                                <span class="property-name">width:</span>
                                <span class="property-value" id="avatar-width">150px</span>
                            </div>
                            <div class="css-property">
                                <span class="property-name">cursor:</span>
                                <span class="property-value" id="avatar-cursor">pointer</span>
                            </div>
                            <div class="css-property">
                                <span class="property-name">z-index:</span>
                                <span class="property-value" id="avatar-zindex">2</span>
                            </div>
                            <div class="css-property">
                                <span class="property-name">pointer-events:</span>
                                <span class="property-value" id="avatar-pointer">auto</span>
                            </div>
                        </div>
                        
                        <div class="inspector-card">
                            <h4>Overlay (.avatar-overlay)</h4>
                            <div class="css-property">
                                <span class="property-name">position:</span>
                                <span class="property-value" id="overlay-position">absolute</span>
                            </div>
                            <div class="css-property">
                                <span class="property-name">z-index:</span>
                                <span class="property-value" id="overlay-zindex">3</span>
                            </div>
                            <div class="css-property">
                                <span class="property-name">pointer-events:</span>
                                <span class="property-value" id="overlay-pointer">none</span>
                            </div>
                            <div class="css-property">
                                <span class="property-name">opacity:</span>
                                <span class="property-value" id="overlay-opacity">0</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Conflict Detector -->
                <div class="debug-section">
                    <h2 class="debug-header"><i class="fas fa-exclamation-triangle"></i> Potential Conflicts</h2>
                    <div id="conflict-report" class="conflict-detector">
                        <h4>Potential CSS Conflicts Detected:</h4>
                        <ul id="conflict-list">
                            <li>Checking for conflicting styles...</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Fix Suggestions -->
                <div class="debug-section">
                    <h2 class="debug-header"><i class="fas fa-wrench"></i> Recommended Fixes</h2>
                    <div id="fix-recommendations" class="fix-suggestion">
                        <h4>Suggested CSS Adjustments:</h4>
                        <ul id="fix-list">
                            <li>Analyzing current styles...</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Interactive Test Area -->
                <div class="debug-section">
                    <h2 class="debug-header"><i class="fas fa-vial"></i> Interactive Test</h2>
                    <div class="avatar-test-area">
                        <div class="test-avatar-container" id="testAvatarContainer">
                            <img src="<?php echo !empty($user_data['avatar']) ? htmlspecialchars($user_data['avatar']) : 'assets/images/default-avatar.png'; ?>" 
                                 alt="Test Avatar" class="test-avatar" id="testAvatar">
                            <div class="test-overlay" id="testOverlay">
                                <div>
                                    <i class="fas fa-camera"></i><br>
                                    Click to test
                                </div>
                            </div>
                        </div>
                        <input type="file" id="testAvatarInput" accept="image/*" style="display: none;">
                        <p style="color: var(--text-secondary); margin: 1rem 0;">
                            <i class="fas fa-info-circle"></i> Click the avatar above to test upload functionality
                        </p>
                        <div id="testFeedback"></div>
                    </div>
                    
                    <h3 style="color: var(--primary); margin: 1rem 0;">Event Log:</h3>
                    <div class="event-log" id="eventLog"></div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Event logging function
        function logEvent(message, type = 'info') {
            const log = document.getElementById('eventLog');
            const entry = document.createElement('div');
            entry.className = `log-entry log-${type}`;
            entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            log.appendChild(entry);
            log.scrollTop = log.scrollHeight;
        }
        
        // Update CSS property displays
        function updateInspector() {
            const container = document.querySelector('.avatar-preview-container') || document.getElementById('testAvatarContainer');
            const avatar = document.querySelector('.avatar-preview') || document.getElementById('testAvatar');
            const overlay = document.querySelector('.avatar-overlay') || document.getElementById('testOverlay');
            
            if (container) {
                document.getElementById('container-position').textContent = getComputedStyle(container).position;
                document.getElementById('container-cursor').textContent = getComputedStyle(container).cursor;
                document.getElementById('container-zindex').textContent = getComputedStyle(container).zIndex;
                document.getElementById('container-pointer').textContent = getComputedStyle(container).pointerEvents;
            }
            
            if (avatar) {
                document.getElementById('avatar-width').textContent = getComputedStyle(avatar).width;
                document.getElementById('avatar-cursor').textContent = getComputedStyle(avatar).cursor;
                document.getElementById('avatar-zindex').textContent = getComputedStyle(avatar).zIndex;
                document.getElementById('avatar-pointer').textContent = getComputedStyle(avatar).pointerEvents;
            }
            
            if (overlay) {
                document.getElementById('overlay-position').textContent = getComputedStyle(overlay).position;
                document.getElementById('overlay-zindex').textContent = getComputedStyle(overlay).zIndex;
                document.getElementById('overlay-pointer').textContent = getComputedStyle(overlay).pointerEvents;
                document.getElementById('overlay-opacity').textContent = getComputedStyle(overlay).opacity;
            }
        }
        
        // Detect CSS conflicts
        function detectConflicts() {
            const conflicts = [];
            const fixes = [];
            
            // Check for common conflict scenarios
            const avatar = document.getElementById('testAvatar');
            if (avatar) {
                const computedStyle = getComputedStyle(avatar);
                
                // Check if avatar is clickable
                if (computedStyle.pointerEvents === 'none') {
                    conflicts.push('Avatar has pointer-events: none');
                    fixes.push('Add pointer-events: auto to .avatar-preview');
                }
                
                // Check z-index stacking
                const containerZ = parseInt(getComputedStyle(document.getElementById('testAvatarContainer')).zIndex) || 0;
                const avatarZ = parseInt(computedStyle.zIndex) || 0;
                const overlayZ = parseInt(getComputedStyle(document.getElementById('testOverlay')).zIndex) || 0;
                
                if (avatarZ <= containerZ) {
                    conflicts.push('Avatar z-index may be too low');
                    fixes.push('Ensure avatar z-index is higher than container');
                }
                
                if (overlayZ <= avatarZ) {
                    conflicts.push('Overlay z-index may interfere with clicks');
                    fixes.push('Set overlay pointer-events: none or adjust z-index stacking');
                }
            }
            
            // Update conflict report
            const conflictList = document.getElementById('conflict-list');
            if (conflicts.length > 0) {
                conflictList.innerHTML = conflicts.map(conflict => `<li>${conflict}</li>`).join('');
            } else {
                conflictList.innerHTML = '<li>No major conflicts detected</li>';
            }
            
            // Update fix recommendations
            const fixList = document.getElementById('fix-list');
            if (fixes.length > 0) {
                fixList.innerHTML = fixes.map(fix => `<li>${fix}</li>`).join('');
            } else {
                fixList.innerHTML = '<li>Current CSS configuration appears correct</li>';
            }
        }
        
        // Test avatar functionality
        document.addEventListener('DOMContentLoaded', function() {
            logEvent('Debug tool loaded', 'success');
            
            const testAvatar = document.getElementById('testAvatar');
            const testContainer = document.getElementById('testAvatarContainer');
            const testInput = document.getElementById('testAvatarInput');
            const testFeedback = document.getElementById('testFeedback');
            
            // Test click events
            testAvatar.addEventListener('click', function(e) {
                e.preventDefault();
                logEvent('Avatar image clicked', 'success');
                testInput.click();
            });
            
            testContainer.addEventListener('click', function(e) {
                if (e.target === testContainer || e.target.classList.contains('test-overlay')) {
                    e.preventDefault();
                    logEvent('Avatar container clicked', 'success');
                    testInput.click();
                }
            });
            
            // Test file input
            testInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    logEvent(`File selected: ${file.name}`, 'success');
                    
                    // Validate and preview
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            testAvatar.src = e.target.result;
                            testFeedback.innerHTML = '<div style="color: var(--success); padding: 10px; background: rgba(16, 185, 129, 0.1); border-radius: 5px; margin: 10px 0;"><i class="fas fa-check-circle"></i> Image preview updated successfully!</div>';
                            logEvent('Image preview updated', 'success');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        testFeedback.innerHTML = '<div style="color: var(--danger); padding: 10px; background: rgba(239, 68, 68, 0.1); border-radius: 5px; margin: 10px 0;"><i class="fas fa-exclamation-circle"></i> Please select an image file.</div>';
                        logEvent('Invalid file type selected', 'error');
                    }
                }
            });
            
            // Initialize inspectors
            updateInspector();
            detectConflicts();
            
            // Update periodically
            setInterval(updateInspector, 1000);
            
            logEvent('All debug systems initialized', 'success');
        });
    </script>
</body>
</html>