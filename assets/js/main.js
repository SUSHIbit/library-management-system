/**
 * Main JavaScript File - Library Management System
 * 
 * This file contains all the core JavaScript functionality for the library
 * management system including navigation, dropdowns, modals, form validation,
 * and AJAX utilities.
 * 
 * @author Final Year Student
 * @version 1.0
 */

'use strict';

// Global application object
window.LibraryApp = {
    config: {
        baseUrl: '',
        assetsUrl: '',
        csrfToken: '',
        userRole: '',
        userId: null,
        debug: false
    },
    
    // Initialize application
    init: function() {
        this.bindEvents();
        this.initializeComponents();
        console.log('Library Management System initialized');
    },
    
    // Bind global events
    bindEvents: function() {
        document.addEventListener('DOMContentLoaded', this.onDOMReady.bind(this));
        window.addEventListener('resize', this.onWindowResize.bind(this));
        window.addEventListener('beforeunload', this.onBeforeUnload.bind(this));
    },
    
    // DOM ready handler
    onDOMReady: function() {
        this.initializeNavigation();
        this.initializeDropdowns();
        this.initializeModals();
        this.initializeTooltips();
        this.initializeFormValidation();
        this.initializeAjaxSetup();
        this.initializeNotifications();
    },
    
    // Window resize handler
    onWindowResize: function() {
        this.handleResponsiveLayout();
    },
    
    // Before unload handler
    onBeforeUnload: function(e) {
        // Check for unsaved changes
        const unsavedForms = document.querySelectorAll('form[data-unsaved="true"]');
        if (unsavedForms.length > 0) {
            e.preventDefault();
            e.returnValue = '';
            return '';
        }
    }
};

/**
 * Navigation Management
 */
LibraryApp.Navigation = {
    
    // Initialize navigation functionality
    init: function() {
        this.bindSidebarEvents();
        this.bindMenuEvents();
        this.handleActiveStates();
    },
    
    // Bind sidebar events
    bindSidebarEvents: function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarCollapse = document.getElementById('sidebarCollapseBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebar = document.getElementById('sidebar');
        
        // Mobile sidebar toggle
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                document.body.classList.toggle('sidebar-open');
            });
        }
        
        // Desktop sidebar collapse
        if (sidebarCollapse) {
            sidebarCollapse.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            });
        }
        
        // Overlay click to close sidebar
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                document.body.classList.remove('sidebar-open');
            });
        }
        
        // Restore sidebar state
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed');
        if (sidebarCollapsed === 'true' && window.innerWidth > 1024) {
            sidebar.classList.add('collapsed');
        }
    },
    
    // Bind menu events
    bindMenuEvents: function() {
        const submenuToggles = document.querySelectorAll('.submenu-toggle');
        
        submenuToggles.forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const menuItem = this.parentElement;
                const isOpen = menuItem.classList.contains('submenu-open');
                
                // Close other submenus
                document.querySelectorAll('.nav-item.submenu-open').forEach(function(item) {
                    if (item !== menuItem) {
                        item.classList.remove('submenu-open');
                    }
                });
                
                // Toggle current submenu
                menuItem.classList.toggle('submenu-open', !isOpen);
            });
        });
    },
    
    // Handle active menu states
    handleActiveStates: function() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link[href]');
        
        navLinks.forEach(function(link) {
            const href = link.getAttribute('href');
            if (currentPath.includes(href)) {
                const navItem = link.closest('.nav-item');
                if (navItem) {
                    navItem.classList.add('active');
                    
                    // Open parent submenu if needed
                    const parentSubmenu = navItem.closest('.nav-submenu');
                    if (parentSubmenu) {
                        const parentItem = parentSubmenu.closest('.nav-item');
                        if (parentItem) {
                            parentItem.classList.add('submenu-open');
                        }
                    }
                }
            }
        });
    }
};

/**
 * Dropdown Management
 */
LibraryApp.Dropdown = {
    
    // Initialize dropdowns
    init: function() {
        this.bindEvents();
    },
    
    // Bind dropdown events
    bindEvents: function() {
        document.addEventListener('click', this.handleGlobalClick.bind(this));
        
        const dropdownToggles = document.querySelectorAll('[data-toggle="dropdown"]');
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', this.toggle.bind(this));
        });
    },
    
    // Toggle dropdown
    toggle: function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const toggle = e.currentTarget;
        const dropdown = toggle.closest('.dropdown');
        const isOpen = dropdown.classList.contains('show');
        
        // Close all other dropdowns
        this.closeAll();
        
        // Toggle current dropdown
        if (!isOpen) {
            dropdown.classList.add('show');
        }
    },
    
    // Close all dropdowns
    closeAll: function() {
        const openDropdowns = document.querySelectorAll('.dropdown.show');
        openDropdowns.forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    },
    
    // Handle global clicks
    handleGlobalClick: function(e) {
        if (!e.target.closest('.dropdown')) {
            this.closeAll();
        }
    }
};

/**
 * Modal Management
 */
LibraryApp.Modal = {
    
    // Initialize modals
    init: function() {
        this.bindEvents();
    },
    
    // Bind modal events
    bindEvents: function() {
        const modalTriggers = document.querySelectorAll('[data-toggle="modal"]');
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', this.show.bind(this));
        });
        
        const modalCloses = document.querySelectorAll('[data-dismiss="modal"], .modal-close');
        modalCloses.forEach(close => {
            close.addEventListener('click', this.hide.bind(this));
        });
        
        // Close modal on overlay click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                LibraryApp.Modal.hide(e);
            }
        });
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    LibraryApp.Modal.hide({ target: openModal });
                }
            }
        });
    },
    
    // Show modal
    show: function(e) {
        e.preventDefault();
        
        const trigger = e.currentTarget;
        const target = trigger.getAttribute('data-target') || trigger.getAttribute('href');
        const modal = document.querySelector(target);
        
        if (modal) {
            modal.classList.add('show');
            document.body.classList.add('modal-open');
            
            // Set focus to modal
            modal.focus();
            
            // Trigger custom event
            modal.dispatchEvent(new CustomEvent('modal:show'));
        }
    },
    
    // Hide modal
    hide: function(e) {
        if (e) {
            e.preventDefault();
        }
        
        const modal = e ? e.target.closest('.modal') : document.querySelector('.modal.show');
        
        if (modal) {
            modal.classList.remove('show');
            document.body.classList.remove('modal-open');
            
            // Trigger custom event
            modal.dispatchEvent(new CustomEvent('modal:hide'));
        }
    },
    
    // Show confirmation modal
    confirm: function(message, callback) {
        const modal = document.getElementById('confirmModal');
        if (modal) {
            const messageElement = document.getElementById('confirmMessage');
            const confirmButton = document.getElementById('confirmButton');
            
            if (messageElement) {
                messageElement.textContent = message;
            }
            
            if (confirmButton) {
                // Remove existing listeners
                const newButton = confirmButton.cloneNode(true);
                confirmButton.parentNode.replaceChild(newButton, confirmButton);
                
                // Add new listener
                newButton.addEventListener('click', function() {
                    LibraryApp.Modal.hide({ target: modal });
                    if (callback) callback();
                });
            }
            
            modal.classList.add('show');
            document.body.classList.add('modal-open');
        }
    }
};

/**
 * Form Management
 */
LibraryApp.Form = {
    
    // Initialize form functionality
    init: function() {
        this.bindValidation();
        this.bindUnsavedChanges();
        this.bindAjaxForms();
    },
    
    // Bind form validation
    bindValidation: function() {
        const forms = document.querySelectorAll('form[data-validate="true"]');
        
        forms.forEach(form => {
            form.addEventListener('submit', this.validateForm.bind(this));
            
            // Real-time validation
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('blur', this.validateField.bind(this));
                input.addEventListener('input', this.clearFieldError.bind(this));
            });
        });
    },
    
    // Validate entire form
    validateForm: function(e) {
        const form = e.target;
        const inputs = form.querySelectorAll('[required], [data-validate]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField({ target: input })) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            
            // Focus first invalid field
            const firstError = form.querySelector('.form-error:not(:empty)');
            if (firstError) {
                const input = firstError.previousElementSibling;
                if (input) input.focus();
            }
        }
        
        return isValid;
    },
    
    // Validate single field
    validateField: function(e) {
        const input = e.target;
        const value = input.value.trim();
        const type = input.type;
        const required = input.hasAttribute('required');
        let errorMessage = '';
        
        // Clear previous error
        this.clearFieldError(e);
        
        // Required validation
        if (required && !value) {
            errorMessage = 'This field is required.';
        }
        
        // Type-specific validation
        if (value && !errorMessage) {
            switch (type) {
                case 'email':
                    if (!this.isValidEmail(value)) {
                        errorMessage = 'Please enter a valid email address.';
                    }
                    break;
                    
                case 'url':
                    if (!this.isValidUrl(value)) {
                        errorMessage = 'Please enter a valid URL.';
                    }
                    break;
                    
                case 'tel':
                    if (!this.isValidPhone(value)) {
                        errorMessage = 'Please enter a valid phone number.';
                    }
                    break;
                    
                case 'number':
                    const min = input.getAttribute('min');
                    const max = input.getAttribute('max');
                    const numValue = parseFloat(value);
                    
                    if (isNaN(numValue)) {
                        errorMessage = 'Please enter a valid number.';
                    } else if (min && numValue < parseFloat(min)) {
                        errorMessage = `Value must be at least ${min}.`;
                    } else if (max && numValue > parseFloat(max)) {
                        errorMessage = `Value must not exceed ${max}.`;
                    }
                    break;
            }
        }
        
        // Length validation
        if (value && !errorMessage) {
            const minLength = input.getAttribute('minlength');
            const maxLength = input.getAttribute('maxlength');
            
            if (minLength && value.length < parseInt(minLength)) {
                errorMessage = `Must be at least ${minLength} characters long.`;
            } else if (maxLength && value.length > parseInt(maxLength)) {
                errorMessage = `Must not exceed ${maxLength} characters.`;
            }
        }
        
        // Custom validation
        const customValidation = input.getAttribute('data-validate');
        if (value && !errorMessage && customValidation) {
            switch (customValidation) {
                case 'password':
                    if (value.length < 6) {
                        errorMessage = 'Password must be at least 6 characters long.';
                    }
                    break;
                    
                case 'password-confirm':
                    const passwordField = document.querySelector('input[type="password"]');
                    if (passwordField && value !== passwordField.value) {
                        errorMessage = 'Passwords do not match.';
                    }
                    break;
                    
                case 'username':
                    if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                        errorMessage = 'Username can only contain letters, numbers, and underscores.';
                    }
                    break;
            }
        }
        
        // Show error if any
        if (errorMessage) {
            this.showFieldError(input, errorMessage);
            return false;
        }
        
        return true;
    },
    
    // Show field error
    showFieldError: function(input, message) {
        input.classList.add('is-invalid');
        
        let errorElement = input.parentNode.querySelector('.form-error');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'form-error';
            input.parentNode.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
    },
    
    // Clear field error
    clearFieldError: function(e) {
        const input = e.target;
        input.classList.remove('is-invalid');
        
        const errorElement = input.parentNode.querySelector('.form-error');
        if (errorElement) {
            errorElement.textContent = '';
        }
    },
    
    // Bind unsaved changes tracking
    bindUnsavedChanges: function() {
        const forms = document.querySelectorAll('form[data-track-changes="true"]');
        
        forms.forEach(form => {
            const inputs = form.querySelectorAll('input, select, textarea');
            
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    form.setAttribute('data-unsaved', 'true');
                });
            });
            
            form.addEventListener('submit', function() {
                form.removeAttribute('data-unsaved');
            });
        });
    },
    
    // Bind AJAX forms
    bindAjaxForms: function() {
        const ajaxForms = document.querySelectorAll('form[data-ajax="true"]');
        
        ajaxForms.forEach(form => {
            form.addEventListener('submit', this.handleAjaxSubmit.bind(this));
        });
    },
    
    // Handle AJAX form submission
    handleAjaxSubmit: function(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const url = form.action || window.location.href;
        const method = form.method || 'POST';
        
        // Add CSRF token
        if (window.csrfToken) {
            formData.append('csrf_token', window.csrfToken);
        }
        
        // Show loading state
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton ? submitButton.textContent : '';
        
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Processing...';
        }
        
        // Submit form
        fetch(url, {
            method: method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                LibraryApp.Notification.show('success', data.message || 'Operation completed successfully.');
                
                // Redirect if specified
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                }
                
                // Reset form if specified
                if (data.reset) {
                    form.reset();
                    form.removeAttribute('data-unsaved');
                }
            } else {
                LibraryApp.Notification.show('error', data.message || 'An error occurred.');
                
                // Show field errors if provided
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const input = form.querySelector(`[name="${field}"]`);
                        if (input) {
                            this.showFieldError(input, data.errors[field]);
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Form submission error:', error);
            LibraryApp.Notification.show('error', 'An unexpected error occurred.');
        })
        .finally(() => {
            // Restore button state
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        });
    },
    
    // Validation helper methods
    isValidEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    isValidUrl: function(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    },
    
    isValidPhone: function(phone) {
        const re = /^[\+]?[\d\s\-\(\)]{10,}$/;
        return re.test(phone);
    }
};

/**
 * Notification Management
 */
LibraryApp.Notification = {
    
    // Initialize notifications
    init: function() {
        this.bindEvents();
        this.autoHideAlerts();
    },
    
    // Bind notification events
    bindEvents: function() {
        // Notification count update
        this.updateNotificationCount();
        
        // Mark as read functionality
        const markAllRead = document.querySelector('.mark-all-read');
        if (markAllRead) {
            markAllRead.addEventListener('click', this.markAllAsRead.bind(this));
        }
        
        // Individual notification clicks
        const notificationItems = document.querySelectorAll('.notification-item');
        notificationItems.forEach(item => {
            item.addEventListener('click', this.markAsRead.bind(this));
        });
    },
    
    // Show notification
    show: function(type, message, duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} fade-in`;
        
        notification.innerHTML = `
            <div class="notification-content">${message}</div>
            <button type="button" class="notification-close">
                <span>&times;</span>
            </button>
        `;
        
        // Add to page
        let container = document.querySelector('.notification-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'notification-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
            `;
            document.body.appendChild(container);
        }
        
        container.appendChild(notification);
        
        // Bind close event
        const closeBtn = notification.querySelector('.notification-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.hide(notification);
            });
        }
        
        // Auto hide
        if (duration > 0) {
            setTimeout(() => {
                this.hide(notification);
            }, duration);
        }
        
        return notification;
    },
    
    // Hide notification
    hide: function(notification) {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    },
    
    // Auto-hide alerts
    autoHideAlerts: function() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        
        alerts.forEach(alert => {
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }
            }, 5000);
        });
    },
    
    // Update notification count
    updateNotificationCount: function() {
        // This would typically make an AJAX call to get unread count
        // For now, just updating the display
        const badge = document.getElementById('notificationCount');
        if (badge) {
            const unreadCount = document.querySelectorAll('.notification-item.unread').length;
            badge.textContent = unreadCount;
            badge.style.display = unreadCount > 0 ? 'flex' : 'none';
        }
    },
    
    // Mark all notifications as read
    markAllAsRead: function(e) {
        e.preventDefault();
        
        const unreadItems = document.querySelectorAll('.notification-item.unread');
        unreadItems.forEach(item => {
            item.classList.remove('unread');
        });
        
        this.updateNotificationCount();
        
        // Make AJAX call to mark as read on server
        // fetch('/api/notifications/mark-all-read', { method: 'POST' });
    },
    
    // Mark single notification as read
    markAsRead: function(e) {
        const item = e.currentTarget;
        item.classList.remove('unread');
        this.updateNotificationCount();
        
        // Make AJAX call to mark as read on server
        // const notificationId = item.dataset.id;
        // fetch(`/api/notifications/${notificationId}/read`, { method: 'POST' });
    }
};

/**
 * AJAX Utilities
 */
LibraryApp.Ajax = {
    
    // Setup AJAX defaults
    init: function() {
        // Set default headers for all fetch requests
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            options.headers = options.headers || {};
            
            // Add CSRF token
            if (window.csrfToken) {
                options.headers['X-CSRF-Token'] = window.csrfToken;
            }
            
            // Add common headers
            options.headers['X-Requested-With'] = 'XMLHttpRequest';
            
            return originalFetch(url, options);
        };
    },
    
    // GET request
    get: function(url, options = {}) {
        return fetch(url, {
            method: 'GET',
            ...options
        }).then(this.handleResponse);
    },
    
    // POST request
    post: function(url, data, options = {}) {
        const body = data instanceof FormData ? data : JSON.stringify(data);
        const headers = data instanceof FormData ? {} : { 'Content-Type': 'application/json' };
        
        return fetch(url, {
            method: 'POST',
            body: body,
            headers: headers,
            ...options
        }).then(this.handleResponse);
    },
    
    // PUT request
    put: function(url, data, options = {}) {
        return fetch(url, {
            method: 'PUT',
            body: JSON.stringify(data),
            headers: { 'Content-Type': 'application/json' },
            ...options
        }).then(this.handleResponse);
    },
    
    // DELETE request
    delete: function(url, options = {}) {
        return fetch(url, {
            method: 'DELETE',
            ...options
        }).then(this.handleResponse);
    },
    
    // Handle response
    handleResponse: function(response) {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        }
        
        return response.text();
    }
};

/**
 * Utility Functions
 */
LibraryApp.Utils = {
    
    // Debounce function
    debounce: function(func, wait, immediate) {
        let timeout;
        return function executedFunction() {
            const context = this;
            const args = arguments;
            
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            
            if (callNow) func.apply(context, args);
        };
    },
    
    // Throttle function
    throttle: function(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    // Format currency
    formatCurrency: function(amount, currency = 'RM') {
        return currency + parseFloat(amount).toFixed(2);
    },
    
    // Format date
    formatDate: function(date, format = 'dd/mm/yyyy') {
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        
        switch (format) {
            case 'dd/mm/yyyy':
                return `${day}/${month}/${year}`;
            case 'mm/dd/yyyy':
                return `${month}/${day}/${year}`;
            case 'yyyy-mm-dd':
                return `${year}-${month}-${day}`;
            default:
                return d.toLocaleDateString();
        }
    },
    
    // Get query parameter
    getUrlParameter: function(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    },
    
    // Copy to clipboard
    copyToClipboard: function(text) {
        if (navigator.clipboard) {
            return navigator.clipboard.writeText(text);
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                document.body.removeChild(textArea);
                return Promise.resolve();
            } catch (err) {
                document.body.removeChild(textArea);
                return Promise.reject(err);
            }
        }
    }
};

// Initialize component functions
function initializeNavigation() {
    LibraryApp.Navigation.init();
}

function initializeDropdowns() {
    LibraryApp.Dropdown.init();
}

function initializeModals() {
    LibraryApp.Modal.init();
}

function initializeTooltips() {
    // Simple tooltip implementation
    const tooltipElements = document.querySelectorAll('[title]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.title;
            tooltip.style.cssText = `
                position: absolute;
                background: #18181b;
                color: white;
                padding: 6px 10px;
                border-radius: 4px;
                font-size: 12px;
                z-index: 9999;
                pointer-events: none;
                white-space: nowrap;
            `;
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
            
            this.addEventListener('mouseleave', function() {
                if (tooltip.parentNode) {
                    tooltip.parentNode.removeChild(tooltip);
                }
            }, { once: true });
        });
    });
}

function initializeFormValidation() {
    LibraryApp.Form.init();
}

function initializeAjaxSetup() {
    LibraryApp.Ajax.init();
}

function initializeNotifications() {
    LibraryApp.Notification.init();
}

function initializeComponents() {
    // Initialize all components
    initializeNavigation();
    initializeDropdowns();
    initializeModals();
    initializeTooltips();
    initializeFormValidation();
    initializeAjaxSetup();
    initializeNotifications();
}

// Responsive layout handler
LibraryApp.handleResponsiveLayout = function() {
    const sidebar = document.getElementById('sidebar');
    const isMobile = window.innerWidth <= 1024;
    
    if (isMobile) {
        sidebar.classList.remove('collapsed');
        sidebar.classList.remove('show');
        document.body.classList.remove('sidebar-open');
    } else {
        const wasCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (wasCollapsed) {
            sidebar.classList.add('collapsed');
        }
    }
};

// Check for overdue books (for students/staff)
function checkOverdueBooks() {
    if (window.appSettings && ['student', 'staff'].includes(window.appSettings.userRole)) {
        // This would typically make an AJAX call to check for overdue books
        // For demo purposes, just showing a notification
        setTimeout(() => {
            const hasOverdue = Math.random() > 0.7; // 30% chance for demo
            if (hasOverdue) {
                LibraryApp.Notification.show('warning', 'You have overdue books. Please return them to avoid fines.', 0);
            }
        }, 2000);
    }
}

// Update notification count
function updateNotificationCount() {
    LibraryApp.Notification.updateNotificationCount();
}

// Initialize application when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    LibraryApp.init();
});

// Export for global access
window.LibraryApp = LibraryApp;