/*
 * Furom Mobile Enhancements - V5
 * JavaScript optimizations for mobile devices
 */

document.addEventListener('DOMContentLoaded', function() {
    // Detect mobile device
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    
    if (isMobile || isTouchDevice) {
        initializeMobileEnhancements();
    }
});

function initializeMobileEnhancements() {
    // Mobile-specific optimizations
    optimizeTouchTargets();
    enhanceMobileNavigation();
    improveScrolling();
    handleOrientationChanges();
    optimizeForms();
    enhanceImages();
}

function optimizeTouchTargets() {
    // Ensure minimum touch target sizes
    const interactiveElements = document.querySelectorAll('button, .btn, .nav-link, .vote-btn, a');
    
    interactiveElements.forEach(element => {
        const computedStyle = window.getComputedStyle(element);
        const minWidth = parseInt(computedStyle.minWidth) || 0;
        const minHeight = parseInt(computedStyle.minHeight) || 0;
        
        if (minWidth < 44) {
            element.style.minWidth = '44px';
        }
        
        if (minHeight < 44) {
            element.style.minHeight = '44px';
        }
        
        // Add touch feedback
        element.addEventListener('touchstart', function() {
            this.classList.add('touch-active');
        });
        
        element.addEventListener('touchend', function() {
            setTimeout(() => {
                this.classList.remove('touch-active');
            }, 150);
        });
    });
}

function enhanceMobileNavigation() {
    // Mobile menu toggle
    const menuToggle = document.createElement('button');
    menuToggle.className = 'mobile-menu-toggle';
    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
    menuToggle.setAttribute('aria-label', 'Toggle menu');
    
    const header = document.querySelector('.cyber-header');
    if (header) {
        header.prepend(menuToggle);
        
        const nav = document.querySelector('.main-nav');
        const userActions = document.querySelector('.user-actions');
        
        menuToggle.addEventListener('click', function() {
            nav.classList.toggle('mobile-visible');
            userActions.classList.toggle('mobile-visible');
            this.classList.toggle('active');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!header.contains(e.target) && !menuToggle.contains(e.target)) {
                nav.classList.remove('mobile-visible');
                userActions.classList.remove('mobile-visible');
                menuToggle.classList.remove('active');
            }
        });
    }
}

function improveScrolling() {
    // Smooth scrolling for mobile
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Prevent zoom on input focus
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            if (this.style.fontSize !== '16px') {
                this.dataset.originalFontSize = this.style.fontSize || '';
                this.style.fontSize = '16px';
            }
        });
        
        input.addEventListener('blur', function() {
            if (this.dataset.originalFontSize) {
                this.style.fontSize = this.dataset.originalFontSize;
            }
        });
    });
}

function handleOrientationChanges() {
    // Handle device orientation changes
    window.addEventListener('orientationchange', function() {
        setTimeout(function() {
            // Re-optimize layout after orientation change
            document.body.style.height = 'auto';
            window.scrollTo(0, 0);
        }, 100);
    });
}

function optimizeForms() {
    // Mobile form optimizations
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // Add proper autocomplete attributes
        const inputs = form.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            if (!input.hasAttribute('autocomplete')) {
                if (input.type === 'email') {
                    input.setAttribute('autocomplete', 'email');
                } else if (input.type === 'password') {
                    input.setAttribute('autocomplete', 'current-password');
                } else if (input.name && input.name.includes('username')) {
                    input.setAttribute('autocomplete', 'username');
                }
            }
        });
        
        // Prevent form zoom on iOS
        form.addEventListener('submit', function() {
            const activeElement = document.activeElement;
            if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA')) {
                activeElement.blur();
            }
        });
    });
}

function enhanceImages() {
    // Optimize images for mobile
    const images = document.querySelectorAll('img');
    
    images.forEach(img => {
        // Add loading attribute for better performance
        if (!img.hasAttribute('loading')) {
            img.setAttribute('loading', 'lazy');
        }
        
        // Prevent image dragging on mobile
        img.addEventListener('dragstart', function(e) {
            e.preventDefault();
        });
    });
}

// Add CSS for mobile enhancements
const mobileStyles = `
    /* Mobile menu toggle styles */
    .mobile-menu-toggle {
        display: none;
        background: transparent;
        border: none;
        color: var(--text-primary);
        font-size: 1.5rem;
        padding: 0.5rem;
        cursor: pointer;
        z-index: 1001;
    }
    
    .mobile-menu-toggle.active {
        color: var(--primary);
    }
    
    /* Touch feedback */
    .touch-active {
        transform: scale(0.95) !important;
        transition: transform 0.1s ease !important;
    }
    
    /* Mobile navigation visibility */
    @media (max-width: 768px) {
        .mobile-menu-toggle {
            display: block;
        }
        
        .main-nav:not(.mobile-visible),
        .user-actions:not(.mobile-visible) {
            display: none;
        }
        
        .main-nav.mobile-visible,
        .user-actions.mobile-visible {
            display: flex;
            flex-direction: column;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-top: none;
            padding: 1rem;
            z-index: 1000;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        .user-actions.mobile-visible {
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .user-actions.mobile-visible .btn {
            width: 100%;
            text-align: center;
        }
    }
`;

// Inject mobile styles
const styleSheet = document.createElement('style');
styleSheet.textContent = mobileStyles;
document.head.appendChild(styleSheet);

// Performance monitoring for mobile
if ('performance' in window) {
    window.addEventListener('load', function() {
        setTimeout(function() {
            const perfData = performance.getEntriesByType('navigation')[0];
            if (perfData) {
                console.log('Mobile Performance Metrics:', {
                    loadTime: perfData.loadEventEnd - perfData.fetchStart,
                    domContentLoaded: perfData.domContentLoadedEventEnd - perfData.fetchStart,
                    firstPaint: performance.getEntriesByType('paint')[0]?.startTime || 'N/A'
                });
            }
        }, 0);
    });
}