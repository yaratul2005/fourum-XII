<?php
// Mobile Responsiveness Test Page
require_once 'config.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#00f5ff">
    <title>Mobile Test - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .device-info {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .test-section {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
        }
        
        .test-section h3 {
            color: var(--primary);
            margin-top: 0;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .touch-target {
            display: inline-block;
            padding: 15px 25px;
            margin: 10px;
            background: var(--primary);
            color: var(--darker-bg);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
            min-width: 44px;
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .touch-target:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 245, 255, 0.4);
        }
        
        .touch-target:active {
            transform: scale(0.95);
        }
        
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .test-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            text-align: center;
        }
        
        .breakpoint-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            background: var(--primary);
            color: var(--darker-bg);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            z-index: 9999;
            box-shadow: 0 5px 15px rgba(0, 245, 255, 0.4);
        }
        
        .orientation-info {
            position: fixed;
            bottom: 10px;
            left: 10px;
            background: var(--secondary);
            color: var(--darker-bg);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            z-index: 9999;
        }
        
        @media (max-width: 768px) {
            .test-container {
                padding: 15px;
            }
            
            .device-info, .test-section {
                padding: 20px;
            }
            
            .test-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .test-container {
                padding: 10px;
            }
            
            .device-info, .test-section {
                padding: 15px;
            }
            
            .touch-target {
                padding: 12px 20px;
                margin: 8px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="breakpoint-indicator" id="breakpointIndicator">Loading...</div>
    <div class="orientation-info" id="orientationInfo">Portrait</div>
    
    <?php include 'includes/header.php'; ?>
    
    <main class="main-container">
        <div class="container">
            <div class="test-container">
                <h1><i class="fas fa-mobile-alt"></i> Mobile Responsiveness Test</h1>
                <p>Test all mobile features and responsiveness across different screen sizes.</p>
                
                <div class="device-info">
                    <h2><i class="fas fa-info-circle"></i> Device Information</h2>
                    <div class="test-grid">
                        <div class="test-item">
                            <h4>Screen Size</h4>
                            <p id="screenSize">Detecting...</p>
                        </div>
                        <div class="test-item">
                            <h4>Viewport</h4>
                            <p id="viewportSize">Detecting...</p>
                        </div>
                        <div class="test-item">
                            <h4>Pixel Ratio</h4>
                            <p id="pixelRatio">Detecting...</p>
                        </div>
                        <div class="test-item">
                            <h4>Touch Support</h4>
                            <p id="touchSupport">Detecting...</p>
                        </div>
                    </div>
                </div>
                
                <div class="test-section">
                    <h3><i class="fas fa-hand-pointer"></i> Touch Target Testing</h3>
                    <p>Test minimum touch target sizes (should be at least 44px):</p>
                    <div style="text-align: center; margin: 20px 0;">
                        <button class="touch-target">Normal Button</button>
                        <button class="touch-target" style="padding: 8px 12px; font-size: 0.8rem;">Small Button</button>
                        <a href="#" class="touch-target" style="text-decoration: none;">Link Button</a>
                        <div class="touch-target" style="background: var(--secondary);">Colored Button</div>
                    </div>
                    <p><small>All buttons should be easily tappable and provide visual feedback when touched.</small></p>
                </div>
                
                <div class="test-section">
                    <h3><i class="fas fa-columns"></i> Layout Testing</h3>
                    <p>Test responsive grid layouts:</p>
                    <div class="test-grid">
                        <div class="test-item">
                            <h4>Card 1</h4>
                            <p>This card should resize based on screen width.</p>
                        </div>
                        <div class="test-item">
                            <h4>Card 2</h4>
                            <p>Testing responsive grid behavior.</p>
                        </div>
                        <div class="test-item">
                            <h4>Card 3</h4>
                            <p>Grid should become single column on mobile.</p>
                        </div>
                    </div>
                </div>
                
                <div class="test-section">
                    <h3><i class="fas fa-font"></i> Typography Testing</h3>
                    <div style="line-height: 1.6;">
                        <h1>Heading 1 - Should be readable</h1>
                        <h2>Heading 2 - Should scale appropriately</h2>
                        <h3>Heading 3 - Mobile optimized</h3>
                        <p>This is regular paragraph text that should be easily readable on mobile devices. The font size should be comfortable for reading without zooming.</p>
                        <p><small>This is small text that should still be legible on mobile screens.</small></p>
                    </div>
                </div>
                
                <div class="test-section">
                    <h3><i class="fas fa-bars"></i> Navigation Testing</h3>
                    <p>Test mobile navigation menu:</p>
                    <ul>
                        <li>Look for the mobile menu toggle (hamburger icon)</li>
                        <li>Menu should slide down when tapped</li>
                        <li>Navigation items should be easily tappable</li>
                        <li>Menu should close when tapping outside</li>
                    </ul>
                </div>
                
                <div class="test-section">
                    <h3><i class="fas fa-images"></i> Image Testing</h3>
                    <div style="text-align: center;">
                        <img src="https://placehold.co/600x400/00f5ff/0a0a1a?text=Responsive+Image" 
                             alt="Test Image" 
                             style="max-width: 100%; height: auto; border-radius: 10px;">
                        <p><small>Image should scale properly and not cause horizontal scrolling</small></p>
                    </div>
                </div>
                
                <div class="test-section">
                    <h3><i class="fas fa-check-circle"></i> Pass/Fail Checklist</h3>
                    <ul style="line-height: 1.8;">
                        <li>No horizontal scrolling on any screen size</li>
                        <li>All interactive elements are at least 44px in size</li>
                        <li>Text is readable without zooming (minimum 16px)</li>
                        <li>Navigation works smoothly on touch devices</li>
                        <li>Forms are easy to use with virtual keyboards</li>
                        <li>Images scale properly and don't break layout</li>
                        <li>Animations are smooth and don't cause jank</li>
                        <li>Content reflows appropriately on orientation change</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Update device information
        function updateDeviceInfo() {
            document.getElementById('screenSize').textContent = 
                `${screen.width} × ${screen.height}px`;
            
            document.getElementById('viewportSize').textContent = 
                `${window.innerWidth} × ${window.innerHeight}px`;
            
            document.getElementById('pixelRatio').textContent = 
                window.devicePixelRatio || '1';
            
            document.getElementById('touchSupport').textContent = 
                'ontouchstart' in window ? 'Yes' : 'No';
        }
        
        // Update breakpoint indicator
        function updateBreakpoint() {
            const width = window.innerWidth;
            let breakpoint = 'Desktop';
            
            if (width <= 480) {
                breakpoint = 'Mobile (≤480px)';
            } else if (width <= 768) {
                breakpoint = 'Tablet (≤768px)';
            } else if (width <= 1024) {
                breakpoint = 'Large Tablet (≤1024px)';
            }
            
            document.getElementById('breakpointIndicator').textContent = breakpoint;
        }
        
        // Update orientation info
        function updateOrientation() {
            const orientation = window.matchMedia('(orientation: portrait)').matches ? 'Portrait' : 'Landscape';
            document.getElementById('orientationInfo').textContent = orientation;
        }
        
        // Initialize
        updateDeviceInfo();
        updateBreakpoint();
        updateOrientation();
        
        // Event listeners
        window.addEventListener('resize', function() {
            updateDeviceInfo();
            updateBreakpoint();
            updateOrientation();
        });
        
        window.addEventListener('orientationchange', function() {
            setTimeout(function() {
                updateDeviceInfo();
                updateOrientation();
            }, 100);
        });
        
        // Touch feedback testing
        document.querySelectorAll('.touch-target').forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
                this.style.boxShadow = '0 2px 8px rgba(0, 245, 255, 0.6)';
            });
            
            button.addEventListener('touchend', function() {
                setTimeout(() => {
                    this.style.transform = '';
                    this.style.boxShadow = '';
                }, 150);
            });
        });
        
        // Test form focus behavior
        const testInput = document.createElement('input');
        testInput.type = 'text';
        testInput.placeholder = 'Test input focus';
        testInput.style.cssText = 'width: 100%; padding: 12px; margin: 10px 0; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-primary); font-size: 16px;';
        
        const formSection = document.querySelector('.test-section:nth-child(4)');
        formSection.appendChild(testInput);
        
        testInput.addEventListener('focus', function() {
            console.log('Input focused - viewport should not zoom');
        });
    </script>
</body>
</html>