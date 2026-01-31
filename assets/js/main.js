// Furom - Main JavaScript Functions

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all functionality
    initializeVoting();
    initializeSharing();
    initializeMobileMenu();
    initializeFormValidation();
    initializeAutoSave();
});

// Voting System
function initializeVoting() {
    // Post voting
    document.querySelectorAll('.vote-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            const voteType = this.classList.contains('upvote') ? 'up' : 'down';
            handleVote(postId, voteType, 'post');
        });
    });

    // Comment voting (will be initialized when comments load)
    initializeCommentVoting();
}

function handleVote(itemId, voteType, itemType) {
    const button = document.querySelector(`[data-${itemType}-id="${itemId}"].${voteType === 'up' ? 'upvote' : 'downvote'}`);
    const voteCount = button.parentElement.querySelector('.vote-count');
    
    // Add loading state
    button.classList.add('loading');
    
    // Send AJAX request
    fetch('ajax/vote.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            item_id: itemId,
            vote_type: voteType,
            item_type: itemType
        })
    })
    .then(response => response.json())
    .then(data => {
        button.classList.remove('loading');
        
        if (data.success) {
            // Update vote count
            voteCount.textContent = data.new_score;
            
            // Update button states
            const upvoteBtn = button.parentElement.querySelector('.upvote');
            const downvoteBtn = button.parentElement.querySelector('.downvote');
            
            // Reset both buttons
            upvoteBtn.classList.remove('active');
            downvoteBtn.classList.remove('active');
            
            // Activate clicked button
            if (data.user_vote === voteType) {
                button.classList.add('active');
            }
            
            // Show feedback
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message || 'Error processing vote', 'error');
        }
    })
    .catch(error => {
        button.classList.remove('loading');
        showNotification('Network error occurred', 'error');
        console.error('Vote error:', error);
    });
}

function initializeCommentVoting() {
    // This will be called when comments are loaded dynamically
    document.addEventListener('commentsLoaded', function() {
        document.querySelectorAll('.comment-vote-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const commentId = this.dataset.commentId;
                const voteType = this.classList.contains('upvote') ? 'up' : 'down';
                handleVote(commentId, voteType, 'comment');
            });
        });
    });
}

// Sharing Functionality
function initializeSharing() {
    document.querySelectorAll('.share-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.dataset.url || window.location.href;
            shareContent(url);
        });
    });
}

function shareContent(url) {
    if (navigator.share) {
        navigator.share({
            title: document.title,
            url: url
        }).catch(console.error);
    } else {
        // Fallback: copy to clipboard
        copyToClipboard(url);
        showNotification('Link copied to clipboard!', 'success');
    }
}

function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
}

// Mobile Menu
function initializeMobileMenu() {
    const mobileToggle = document.createElement('button');
    mobileToggle.className = 'mobile-menu-toggle';
    mobileToggle.innerHTML = '<i class="fas fa-bars"></i>';
    mobileToggle.style.cssText = `
        display: none;
        background: var(--primary);
        color: var(--darker-bg);
        border: none;
        padding: 0.75rem;
        border-radius: 50%;
        cursor: pointer;
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 1001;
        font-size: 1.2rem;
    `;
    
    document.body.appendChild(mobileToggle);
    
    // Toggle mobile menu
    mobileToggle.addEventListener('click', function() {
        document.querySelector('.main-nav').classList.toggle('mobile-active');
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.main-nav') && !e.target.closest('.mobile-menu-toggle')) {
            document.querySelector('.main-nav').classList.remove('mobile-active');
        }
    });
    
    // Show/hide mobile toggle based on screen size
    function toggleMobileMenuVisibility() {
        mobileToggle.style.display = window.innerWidth <= 768 ? 'block' : 'none';
    }
    
    toggleMobileMenuVisibility();
    window.addEventListener('resize', toggleMobileMenuVisibility);
}

// Form Validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const fields = form.querySelectorAll('[required]');
    
    fields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name || field.id;
    let isValid = true;
    let errorMessage = '';
    
    // Clear previous errors
    clearFieldError(field);
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        errorMessage = `${getFieldLabel(fieldName)} is required`;
        isValid = false;
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            errorMessage = 'Please enter a valid email address';
            isValid = false;
        }
    }
    
    // Password validation
    if (field.type === 'password' && value) {
        if (value.length < 6) {
            errorMessage = 'Password must be at least 6 characters';
            isValid = false;
        }
    }
    
    // Username validation
    if (fieldName === 'username' && value) {
        const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
        if (!usernameRegex.test(value)) {
            errorMessage = 'Username must be 3-20 characters and contain only letters, numbers, and underscores';
            isValid = false;
        }
    }
    
    if (!isValid) {
        showFieldError(field, errorMessage);
    }
    
    return isValid;
}

function getFieldLabel(fieldName) {
    const labels = {
        'username': 'Username',
        'email': 'Email',
        'password': 'Password',
        'title': 'Title',
        'content': 'Content',
        'category': 'Category'
    };
    return labels[fieldName] || fieldName.charAt(0).toUpperCase() + fieldName.slice(1);
}

function showFieldError(field, message) {
    field.classList.add('error');
    let errorElement = field.parentNode.querySelector('.error-message');
    if (!errorElement) {
        errorElement = document.createElement('span');
        errorElement.className = 'error-message';
        field.parentNode.appendChild(errorElement);
    }
    errorElement.textContent = message;
}

function clearFieldError(field) {
    field.classList.remove('error');
    const errorElement = field.parentNode.querySelector('.error-message');
    if (errorElement) {
        errorElement.remove();
    }
}

// Auto-save functionality
function initializeAutoSave() {
    const autoSaveForms = document.querySelectorAll('[data-auto-save]');
    
    autoSaveForms.forEach(form => {
        const formDataKey = `autosave_${form.id || 'form'}`;
        let saveTimeout;
        
        // Load saved data
        const savedData = localStorage.getItem(formDataKey);
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                Object.keys(data).forEach(key => {
                    const field = form.querySelector(`[name="${key}"]`);
                    if (field) {
                        field.value = data[key];
                    }
                });
            } catch (e) {
                console.error('Error loading auto-saved data:', e);
            }
        }
        
        // Save data periodically
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    saveFormData(form, formDataKey);
                }, 2000);
            });
        });
        
        // Clear saved data on successful submit
        form.addEventListener('submit', function() {
            localStorage.removeItem(formDataKey);
        });
    });
}

function saveFormData(form, key) {
    const data = {};
    const inputs = form.querySelectorAll('input, textarea, select');
    
    inputs.forEach(input => {
        if (input.name && input.value) {
            data[input.name] = input.value;
        }
    });
    
    localStorage.setItem(key, JSON.stringify(data));
}

// Notification System
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    // Add styles if not already added
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                min-width: 300px;
                animation: slideInRight 0.3s ease;
            }
            
            .notification-content {
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 1rem;
                border-radius: 10px;
                color: white;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            }
            
            .notification-success .notification-content {
                background: linear-gradient(45deg, var(--success), #00cc88);
            }
            
            .notification-error .notification-content {
                background: linear-gradient(45deg, var(--danger), #cc3333);
            }
            
            .notification-info .notification-content {
                background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            }
            
            .notification-warning .notification-content {
                background: linear-gradient(45deg, var(--warning), #ff9900);
            }
            
            .notification-close {
                background: transparent;
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                margin-left: auto;
            }
            
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        closeNotification(notification);
    }, 5000);
    
    // Close button event
    notification.querySelector('.notification-close').addEventListener('click', () => {
        closeNotification(notification);
    });
}

function getNotificationIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'info': 'info-circle',
        'warning': 'exclamation-triangle'
    };
    return icons[type] || 'info-circle';
}

function closeNotification(notification) {
    notification.style.animation = 'slideOutRight 0.3s ease';
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

// Infinite Scroll
function initializeInfiniteScroll() {
    const container = document.querySelector('.posts-container');
    if (!container) return;
    
    let loading = false;
    let page = 1;
    const totalPages = parseInt(document.querySelector('[data-total-pages]')?.dataset.totalPages || '1');
    
    window.addEventListener('scroll', function() {
        if (loading || page >= totalPages) return;
        
        const containerRect = container.getBoundingClientRect();
        const threshold = 500; // pixels from bottom
        
        if (containerRect.bottom <= (window.innerHeight + threshold)) {
            loadMorePosts();
        }
    });
    
    function loadMorePosts() {
        if (loading) return;
        loading = true;
        page++;
        
        // Show loading indicator
        const loader = document.createElement('div');
        loader.className = 'loading-more';
        loader.innerHTML = '<div class="loading-spinner"></div><p>Loading more posts...</p>';
        container.appendChild(loader);
        
        // Fetch more posts
        const params = new URLSearchParams(window.location.search);
        params.set('page', page);
        
        fetch(`index.php?${params.toString()}&ajax=1`)
            .then(response => response.text())
            .then(html => {
                loader.remove();
                container.insertAdjacentHTML('beforeend', html);
                loading = false;
                
                // Re-initialize voting for new posts
                initializeVoting();
            })
            .catch(error => {
                loader.remove();
                loading = false;
                showNotification('Failed to load more posts', 'error');
            });
    }
}

// Search functionality
function initializeSearch() {
    const searchInput = document.querySelector('.search-input');
    if (!searchInput) return;
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length >= 2) {
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        } else {
            clearSearchResults();
        }
    });
}

function performSearch(query) {
    fetch(`ajax/search.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            displaySearchResults(data.results);
        })
        .catch(error => {
            console.error('Search error:', error);
        });
}

function displaySearchResults(results) {
    const resultsContainer = document.querySelector('.search-results');
    if (!resultsContainer) return;
    
    if (results.length === 0) {
        resultsContainer.innerHTML = '<p>No results found</p>';
        return;
    }
    
    resultsContainer.innerHTML = results.map(result => `
        <div class="search-result">
            <a href="${result.url}">
                <h4>${result.title}</h4>
                <p>${result.excerpt}</p>
                <small>${result.type} • ${result.date}</small>
            </a>
        </div>
    `).join('');
}

function clearSearchResults() {
    const resultsContainer = document.querySelector('.search-results');
    if (resultsContainer) {
        resultsContainer.innerHTML = '';
    }
}

// Theme switching (light/dark mode)
function initializeThemeSwitcher() {
    const themeToggle = document.querySelector('.theme-toggle');
    if (!themeToggle) return;
    
    // Check for saved theme
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        // Update icon
        const icon = this.querySelector('i');
        icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    });
}

// Initialize everything when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAll);
} else {
    initializeAll();
}

function initializeAll() {
    initializeVoting();
    initializeSharing();
    initializeMobileMenu();
    initializeFormValidation();
    initializeAutoSave();
    initializeInfiniteScroll();
    initializeSearch();
    initializeThemeSwitcher();
}