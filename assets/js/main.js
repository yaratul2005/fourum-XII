// Furom V2 - Enhanced JavaScript with Advanced Animations
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Furom V2 Enhanced Loaded');
    
    // Initialize all enhanced features
    initializeEnhancedFeatures();
    setupAdvancedAnimations();
    setupSmoothScrolling();
    setupHoverEffects();
    setupAutoSave();
    setupShareFunctionality();
    setupThemeToggle();
    setupParticleEffects();
});

// Enhanced Feature Initialization
function initializeEnhancedFeatures() {
    // Enhanced vote handling with animations
    setupEnhancedVoting();
    
    // Enhanced comment system
    setupEnhancedComments();
    
    // Enhanced dropdown menus
    setupEnhancedDropdowns();
    
    // Enhanced form validation
    setupEnhancedValidation();
    
    // Enhanced notifications
    setupEnhancedNotifications();
}

// Advanced Voting System with Visual Feedback
function setupEnhancedVoting() {
    const voteButtons = document.querySelectorAll('.vote-btn');
    
    voteButtons.forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const postId = this.dataset.postId || this.closest('[data-post-id]')?.dataset.postId;
            const voteType = this.classList.contains('upvote') ? 'up' : 'down';
            const isComment = this.closest('.comment-item') !== null;
            const targetElement = isComment ? this.closest('.comment-item') : this.closest('.post-card');
            
            if (!postId) return;
            
            // Visual feedback animation
            animateVoteButton(this, voteType);
            
            try {
                const formData = new FormData();
                formData.append('post_id', postId);
                formData.append('vote_type', voteType);
                formData.append('is_comment', isComment ? '1' : '0');
                
                const response = await fetch('ajax/vote.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Enhanced score update with animation
                    updateScoreDisplay(targetElement, result.new_score, voteType, isComment);
                    updateVoteButtonState(this, result.user_vote, isComment);
                    showVoteNotification(voteType, result.message);
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                console.error('Vote error:', error);
                showNotification('Failed to process vote', 'error');
            }
        });
        
        // Enhanced hover effects
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.3) rotate(5deg)';
            this.style.textShadow = this.classList.contains('upvote') ? 
                '0 0 15px #00ff9d' : '0 0 15px #ff4757';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
            this.style.textShadow = 'none';
        });
    });
}

// Advanced Vote Button Animation
function animateVoteButton(button, voteType) {
    // Ripple effect
    createRippleEffect(button);
    
    // Pulse animation
    button.style.animation = 'pulse 0.6s ease-in-out';
    setTimeout(() => {
        button.style.animation = '';
    }, 600);
    
    // Color transition
    const colors = {
        up: '#00ff9d',
        down: '#ff4757'
    };
    
    button.style.color = colors[voteType];
    button.style.textShadow = `0 0 20px ${colors[voteType]}`;
    
    setTimeout(() => {
        button.style.color = '';
        button.style.textShadow = '';
    }, 1000);
}

// Enhanced Score Display Update
function updateScoreDisplay(element, newScore, voteType, isComment) {
    const scoreElement = element.querySelector(isComment ? '.comment-score' : '.vote-count');
    if (!scoreElement) return;
    
    const oldScore = parseInt(scoreElement.textContent) || 0;
    const difference = newScore - oldScore;
    
    // Animate the score change
    animateScoreChange(scoreElement, oldScore, newScore, difference, voteType);
}

function animateScoreChange(element, from, to, difference, voteType) {
    const duration = 800;
    const steps = 30;
    const stepTime = duration / steps;
    const increment = difference / steps;
    
    let current = from;
    let step = 0;
    
    const timer = setInterval(() => {
        current += increment;
        step++;
        
        // Add visual effect based on vote type
        const color = voteType === 'up' ? '#00ff9d' : '#ff4757';
        element.style.color = step % 2 === 0 ? color : '#ffffff';
        element.style.textShadow = step % 2 === 0 ? `0 0 15px ${color}` : 'none';
        element.style.transform = `scale(${1 + Math.abs(difference) * 0.1})`;
        
        element.textContent = Math.round(current);
        
        if (step >= steps) {
            clearInterval(timer);
            element.style.color = '#ffffff';
            element.style.textShadow = '0 0 5px rgba(255, 255, 255, 0.3)';
            element.style.transform = 'scale(1)';
        }
    }, stepTime);
}

// Enhanced Vote Button State Management
function updateVoteButtonState(button, userVote, isComment) {
    const container = button.closest(isComment ? '.comment-votes' : '.post-vote');
    const upvoteBtn = container.querySelector('.upvote');
    const downvoteBtn = container.querySelector('.downvote');
    
    // Reset all states
    upvoteBtn.classList.remove('active');
    downvoteBtn.classList.remove('active');
    
    // Set active state
    if (userVote === 'up') {
        upvoteBtn.classList.add('active');
    } else if (userVote === 'down') {
        downvoteBtn.classList.add('active');
    }
}

// Enhanced Comment System
function setupEnhancedComments() {
    const commentForms = document.querySelectorAll('.comment-form');
    
    commentForms.forEach(form => {
        const textarea = form.querySelector('textarea');
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Auto-resize textarea
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            
            // Enhanced submit button effect
            if (this.value.trim().length > 0) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.transform = 'scale(1.05)';
            } else {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
                submitBtn.style.transform = 'scale(1)';
            }
        });
        
        // Enhanced form submission
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Loading state
            submitBtn.innerHTML = '<span class="loading"></span> Posting...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('ajax/create-comment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    this.reset();
                    textarea.style.height = 'auto';
                    
                    // Enhanced comment insertion with animation
                    insertAnimatedComment(result.comment_html);
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                console.error('Comment error:', error);
                showNotification('Failed to post comment', 'error');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    });
}

// Enhanced Comment Insertion with Animation
function insertAnimatedComment(commentHTML) {
    const commentsContainer = document.querySelector('.comments-list');
    if (!commentsContainer) return;
    
    // Create temporary element
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = commentHTML;
    const newComment = tempDiv.firstElementChild;
    
    // Add entrance animation class
    newComment.style.opacity = '0';
    newComment.style.transform = 'translateY(30px) scale(0.8)';
    newComment.style.transition = 'all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
    
    // Insert at the beginning
    commentsContainer.insertBefore(newComment, commentsContainer.firstChild);
    
    // Trigger animation
    setTimeout(() => {
        newComment.style.opacity = '1';
        newComment.style.transform = 'translateY(0) scale(1)';
    }, 50);
    
    // Add hover effects to new comment
    setupCommentHoverEffects(newComment);
}

// Enhanced Dropdown Menus
function setupEnhancedDropdowns() {
    const dropdowns = document.querySelectorAll('.user-dropdown');
    
    dropdowns.forEach(dropdown => {
        const button = dropdown.querySelector('.user-btn');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (!button || !menu) return;
        
        // Enhanced hover effects
        let timeoutId;
        
        dropdown.addEventListener('mouseenter', function() {
            clearTimeout(timeoutId);
            menu.style.opacity = '1';
            menu.style.visibility = 'visible';
            menu.style.transform = 'translateY(0) scale(1) rotateX(0deg)';
            
            // Add 3D effect
            menu.style.transformStyle = 'preserve-3d';
            menu.style.perspective = '1000px';
        });
        
        dropdown.addEventListener('mouseleave', function() {
            timeoutId = setTimeout(() => {
                menu.style.opacity = '0';
                menu.style.visibility = 'hidden';
                menu.style.transform = 'translateY(-15px) scale(0.95) rotateX(-15deg)';
            }, 300);
        });
        
        // Enhanced menu item effects
        const menuItems = menu.querySelectorAll('a');
        menuItems.forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(10px) scale(1.05)';
                this.style.background = 'rgba(0, 245, 255, 0.2)';
                this.style.boxShadow = '0 5px 15px rgba(0, 245, 255, 0.3)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0) scale(1)';
                this.style.background = '';
                this.style.boxShadow = '';
            });
        });
    });
}

// Advanced Form Validation
function setupEnhancedValidation() {
    const forms = document.querySelectorAll('form[data-validate="true"]');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            // Real-time validation
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('invalid')) {
                    validateField(this);
                }
                
                // Enhanced input effects
                this.style.boxShadow = '0 0 0 2px rgba(0, 245, 255, 0.3)';
                this.style.borderColor = '#00f5ff';
                this.style.transform = 'translateY(-2px)';
                
                setTimeout(() => {
                    this.style.boxShadow = '';
                    this.style.borderColor = '';
                    this.style.transform = '';
                }, 300);
            });
        });
        
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Please fix the errors in the form', 'error');
                
                // Shake invalid fields
                const invalidFields = form.querySelectorAll('.invalid');
                invalidFields.forEach(field => {
                    field.style.animation = 'shake 0.5s ease-in-out';
                    setTimeout(() => {
                        field.style.animation = '';
                    }, 500);
                });
            }
        });
    });
}

function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name;
    let isValid = true;
    let errorMessage = '';
    
    // Clear previous errors
    field.classList.remove('invalid', 'valid');
    const existingError = field.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    // Validation rules
    switch(fieldName) {
        case 'username':
            if (value.length < 3) {
                isValid = false;
                errorMessage = 'Username must be at least 3 characters';
            } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                isValid = false;
                errorMessage = 'Username can only contain letters, numbers, and underscores';
            }
            break;
            
        case 'email':
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
            break;
            
        case 'password':
            if (value.length < 6) {
                isValid = false;
                errorMessage = 'Password must be at least 6 characters';
            }
            break;
            
        case 'title':
            if (value.length < 5) {
                isValid = false;
                errorMessage = 'Title must be at least 5 characters';
            }
            break;
            
        case 'content':
            if (value.length < 10) {
                isValid = false;
                errorMessage = 'Content must be at least 10 characters';
            }
            break;
    }
    
    // Apply validation styling
    if (isValid) {
        field.classList.add('valid');
        showSuccessIndicator(field);
    } else {
        field.classList.add('invalid');
        showErrorIndicator(field, errorMessage);
    }
    
    return isValid;
}

function showErrorIndicator(field, message) {
    const errorSpan = document.createElement('span');
    errorSpan.className = 'error-message';
    errorSpan.textContent = message;
    errorSpan.style.animation = 'fadeIn 0.3s ease-out';
    field.parentNode.appendChild(errorSpan);
}

function showSuccessIndicator(field) {
    const successIcon = document.createElement('span');
    successIcon.innerHTML = '✓';
    successIcon.style.color = '#00ff9d';
    successIcon.style.position = 'absolute';
    successIcon.style.right = '10px';
    successIcon.style.top = '50%';
    successIcon.style.transform = 'translateY(-50%)';
    successIcon.style.fontSize = '1.2rem';
    successIcon.style.animation = 'bounceIn 0.6s ease-out';
    
    field.parentNode.style.position = 'relative';
    field.parentNode.appendChild(successIcon);
    
    setTimeout(() => {
        successIcon.remove();
    }, 2000);
}

// Enhanced Notifications System
function setupEnhancedNotifications() {
    // Create notification container
    const notificationContainer = document.createElement('div');
    notificationContainer.id = 'notification-container';
    notificationContainer.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        display: flex;
        flex-direction: column;
        gap: 15px;
        max-width: 400px;
    `;
    document.body.appendChild(notificationContainer);
}

function showNotification(message, type = 'info', duration = 4000) {
    const container = document.getElementById('notification-container');
    if (!container) return;
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        background: ${type === 'success' ? 'linear-gradient(135deg, #0a0a1a, #121225)' : 
                     type === 'error' ? 'linear-gradient(135deg, #1a0a0a, #251212)' : 
                     'linear-gradient(135deg, #0a1a1a, #122525)'};
        border: 1px solid ${type === 'success' ? '#00ff9d' : 
                          type === 'error' ? '#ff4757' : '#00f5ff'};
        color: white;
        padding: 1.25rem 1.5rem;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 
                   0 0 30px ${type === 'success' ? 'rgba(0, 255, 157, 0.3)' : 
                             type === 'error' ? 'rgba(255, 71, 87, 0.3)' : 
                             'rgba(0, 245, 255, 0.3)'};
        transform: translateX(100%);
        opacity: 0;
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        overflow: hidden;
        max-width: 400px;
    `;
    
    // Add icon based on type
    const icons = {
        success: '✓',
        error: '✕',
        info: 'ℹ'
    };
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 1.5rem; font-weight: bold; 
                         color: ${type === 'success' ? '#00ff9d' : 
                                 type === 'error' ? '#ff4757' : '#00f5ff'}">
                ${icons[type]}
            </span>
            <span style="flex: 1;">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" 
                    style="background: none; border: none; color: #a0a0c0; 
                           cursor: pointer; font-size: 1.2rem;">×</button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Entrance animation
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
        notification.style.opacity = '1';
    }, 10);
    
    // Auto-remove
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.transform = 'translateX(100%)';
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 500);
        }
    }, duration);
}

function showVoteNotification(voteType, message) {
    const container = document.getElementById('notification-container');
    if (!container) return;
    
    const notification = document.createElement('div');
    notification.className = 'vote-notification';
    notification.style.cssText = `
        background: linear-gradient(135deg, #0a0a1a, #121225);
        border: 2px solid ${voteType === 'up' ? '#00ff9d' : '#ff4757'};
        color: white;
        padding: 1.5rem;
        border-radius: 15px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6),
                   0 0 40px ${voteType === 'up' ? 'rgba(0, 255, 157, 0.4)' : 'rgba(255, 71, 87, 0.4)'};
        transform: scale(0) rotate(180deg);
        opacity: 0;
        transition: all 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0) rotate(180deg);
        z-index: 10001;
        text-align: center;
        min-width: 300px;
    `;
    
    notification.innerHTML = `
        <div style="font-size: 3rem; margin-bottom: 1rem; 
                    color: ${voteType === 'up' ? '#00ff9d' : '#ff4757'};
                    text-shadow: 0 0 20px ${voteType === 'up' ? '#00ff9d' : '#ff4757'};">
            ${voteType === 'up' ? '⬆️' : '⬇️'}
        </div>
        <div style="font-size: 1.2rem; font-weight: 500;">${message}</div>
    `;
    
    document.body.appendChild(notification);
    
    // Entrance animation
    setTimeout(() => {
        notification.style.transform = 'translate(-50%, -50%) scale(1) rotate(0deg)';
        notification.style.opacity = '1';
    }, 10);
    
    // Auto-remove with enhanced exit
    setTimeout(() => {
        notification.style.transform = 'translate(-50%, -50%) scale(0) rotate(-180deg)';
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 800);
    }, 2000);
}

// Advanced Animations Setup
function setupAdvancedAnimations() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
                entry.target.classList.add('animated');
            }
        });
    }, observerOptions);
    
    // Observe animated elements
    document.querySelectorAll('.post-card, .widget, .form-container').forEach(el => {
        el.style.animation = 'fadeInUp 0.8s ease-out paused';
        observer.observe(el);
    });
    
    // Enhanced hover animations
    setupHoverAnimations();
    
    // Parallax effects
    setupParallaxEffects();
}

function setupHoverAnimations() {
    // Enhanced card hover effects
    document.querySelectorAll('.post-card, .widget').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-15px) scale(1.02) rotateX(5deg)';
            this.style.boxShadow = `
                0 25px 60px rgba(0, 0, 0, 0.5),
                0 0 50px rgba(0, 245, 255, 0.3),
                inset 0 0 40px rgba(0, 245, 255, 0.1)
            `;
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1) rotateX(0deg)';
            this.style.boxShadow = `
                0 10px 30px rgba(0, 0, 0, 0.3),
                0 0 20px rgba(0, 0, 0, 0.1)
            `;
        });
    });
    
    // Enhanced button hover effects
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.05)';
            this.style.boxShadow = `
                0 15px 35px rgba(0, 0, 0, 0.4),
                0 0 40px ${this.classList.contains('btn-primary') ? 
                    'rgba(0, 245, 255, 0.6)' : 'rgba(0, 245, 255, 0.3)'}
            `;
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = '';
        });
    });
}

function setupParallaxEffects() {
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.5;
        
        document.querySelectorAll('.parallax-element').forEach(element => {
            element.style.transform = `translateY(${rate}px)`;
        });
    });
}

// Smooth Scrolling Enhancement
function setupSmoothScrolling() {
    // Enhanced smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'nearest'
                });
                
                // Add highlight effect
                target.style.animation = 'highlight 2s ease-out';
                setTimeout(() => {
                    target.style.animation = '';
                }, 2000);
            }
        });
    });
}

// Enhanced Hover Effects
function setupHoverEffects() {
    // Enhanced link hover effects
    document.querySelectorAll('a:not(.btn)').forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
            this.style.textShadow = '0 0 10px currentColor';
        });
        
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
            this.style.textShadow = '';
        });
    });
    
    // Enhanced image hover effects
    document.querySelectorAll('img').forEach(img => {
        img.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05) rotate(2deg)';
            this.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.3)';
        });
        
        img.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1) rotate(0deg)';
            this.style.boxShadow = '';
        });
    });
}

// Enhanced Auto-Save Functionality
function setupAutoSave() {
    const forms = document.querySelectorAll('form[data-auto-save="true"]');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea');
        const storageKey = `autosave_${form.id || 'default'}`;
        
        // Load saved data
        const savedData = localStorage.getItem(storageKey);
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                inputs.forEach(input => {
                    if (data[input.name]) {
                        input.value = data[input.name];
                        
                        // Trigger input events for proper initialization
                        input.dispatchEvent(new Event('input'));
                    }
                });
                
                showNotification('Draft restored', 'info', 2000);
            } catch (e) {
                console.error('Failed to restore draft:', e);
            }
        }
        
        // Auto-save on input
        inputs.forEach(input => {
            input.addEventListener('input', debounce(() => {
                const formData = new FormData(form);
                const data = {};
                
                for (let [key, value] of formData.entries()) {
                    data[key] = value;
                }
                
                localStorage.setItem(storageKey, JSON.stringify(data));
                
                // Show subtle save indicator
                showSaveIndicator(form);
            }, 1000));
        });
        
        // Clear saved data on successful submit
        form.addEventListener('submit', () => {
            localStorage.removeItem(storageKey);
        });
    });
}

function showSaveIndicator(form) {
    let indicator = form.querySelector('.save-indicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.className = 'save-indicator';
        indicator.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 255, 157, 0.9);
            color: #0a0a1a;
            padding: 0.75rem 1.25rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 500;
            box-shadow: 0 5px 15px rgba(0, 255, 157, 0.3);
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s ease-out;
            z-index: 1000;
        `;
        indicator.innerHTML = '✅ Saved';
        document.body.appendChild(indicator);
    }
    
    // Show indicator
    indicator.style.transform = 'translateX(0)';
    indicator.style.opacity = '1';
    
    // Hide after delay
    setTimeout(() => {
        indicator.style.transform = 'translateX(100%)';
        indicator.style.opacity = '0';
    }, 2000);
}

// Enhanced Share Functionality
function setupShareFunctionality() {
    const shareButtons = document.querySelectorAll('.share-btn');
    
    shareButtons.forEach(button => {
        button.addEventListener('click', function() {
            const url = this.dataset.url || window.location.href;
            const title = document.title;
            
            // Try Web Share API first
            if (navigator.share) {
                navigator.share({
                    title: title,
                    url: url
                }).catch(console.error);
            } else {
                // Fallback: copy to clipboard
                copyToClipboard(url);
                showNotification('Link copied to clipboard!', 'success');
            }
            
            // Enhanced visual feedback
            animateShareButton(this);
        });
    });
}

function animateShareButton(button) {
    button.style.animation = 'bounce 0.6s ease-in-out';
    button.style.transform = 'scale(1.2)';
    button.style.color = '#00f5ff';
    
    setTimeout(() => {
        button.style.animation = '';
        button.style.transform = '';
        button.style.color = '';
    }, 600);
}

function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
    } else {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }
}

// Theme Toggle (Future enhancement)
function setupThemeToggle() {
    // Placeholder for theme switching functionality
    console.log('Theme toggle ready for implementation');
}

// Particle Effects System
function setupParticleEffects() {
    // Create canvas for particle effects
    const canvas = document.createElement('canvas');
    canvas.id = 'particle-canvas';
    canvas.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: -1;
        opacity: 0.3;
    `;
    
    document.body.appendChild(canvas);
    
    // Initialize particles
    initParticles(canvas);
}

function initParticles(canvas) {
    const ctx = canvas.getContext('2d');
    let particles = [];
    const particleCount = 50;
    
    // Set canvas size
    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);
    
    // Create particles
    for (let i = 0; i < particleCount; i++) {
        particles.push({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            vx: (Math.random() - 0.5) * 0.5,
            vy: (Math.random() - 0.5) * 0.5,
            size: Math.random() * 2 + 1,
            opacity: Math.random() * 0.5 + 0.2
        });
    }
    
    // Animation loop
    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        particles.forEach(particle => {
            // Update position
            particle.x += particle.vx;
            particle.y += particle.vy;
            
            // Boundary checks
            if (particle.x < 0 || particle.x > canvas.width) particle.vx *= -1;
            if (particle.y < 0 || particle.y > canvas.height) particle.vy *= -1;
            
            // Draw particle
            ctx.beginPath();
            ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(0, 245, 255, ${particle.opacity})`;
            ctx.fill();
        });
        
        requestAnimationFrame(animate);
    }
    
    animate();
}

// Utility Functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function createRippleEffect(element) {
    const ripple = document.createElement('span');
    ripple.className = 'ripple';
    ripple.style.cssText = `
        position: absolute;
        border-radius: 50%;
        background: rgba(0, 245, 255, 0.4);
        transform: scale(0);
        animation: ripple 0.6s linear;
        pointer-events: none;
    `;
    
    element.appendChild(ripple);
    
    setTimeout(() => {
        ripple.remove();
    }, 600);
}

// Add CSS for new animations
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    @keyframes highlight {
        0% { box-shadow: 0 0 0 0 rgba(0, 245, 255, 0.7); }
        70% { box-shadow: 0 0 0 15px rgba(0, 245, 255, 0); }
        100% { box-shadow: 0 0 0 0 rgba(0, 245, 255, 0); }
    }
    
    .animated {
        animation-play-state: running !important;
    }
    
    .notification {
        backdrop-filter: blur(10px);
        border-left: 4px solid currentColor;
    }
    
    .vote-notification {
        backdrop-filter: blur(15px);
    }
    
    .save-indicator {
        backdrop-filter: blur(10px);
    }
    
    .ripple {
        z-index: 1;
    }
`;
document.head.appendChild(style);

console.log('✨ Furom V2 Enhanced Features Initialized!');