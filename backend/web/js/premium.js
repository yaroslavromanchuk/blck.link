/**
 * Premium Admin Dashboard - Core JavaScript
 * Enhanced functionality and interactivity
 */

(function() {
    'use strict';

    const PremiumAdmin = {
        // Configuration
        config: {
            animationDuration: 300,
            sidebarMinWidth: 80,
            sidebarMaxWidth: 280,
            breakpoint: 768,
        },

        // Initialize the application
        init() {
            this.setupEventListeners();
            this.setupSidebar();
            this.setupTooltips();
            this.setupAlerts();
            this.setupForms();
        },

        // Setup all event listeners
        setupEventListeners() {
            // Menu toggle
            const menuToggle = document.querySelector('.nav_menu-toggle');
            if (menuToggle) {
                menuToggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleSidebar();
                });
            }

            // Sidebar menu items
            const sidebarMenuItems = document.querySelectorAll('.sidebar-menu > li > a');
            sidebarMenuItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    const parent = item.closest('li');
                    const submenu = parent.querySelector('ul');

                    if (submenu) {
                        e.preventDefault();
                        this.toggleSubmenu(parent);
                    }
                });
            });

            // Close alerts
            const alertCloses = document.querySelectorAll('.alert .close');
            alertCloses.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const alert = btn.closest('.alert');
                    this.closeAlert(alert);
                });
            });

            // Form submit handlers
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', (e) => {
                    const submitBtn = form.querySelector('[type="submit"]');
                    if (submitBtn) {
                        submitBtn.classList.add('btn-loading');
                    }
                });
            });
        },

        // Toggle sidebar visibility
        toggleSidebar() {
            const navbar = document.querySelector('.nav-md');
            if (window.innerWidth <= this.config.breakpoint) {
                navbar.classList.toggle('open');
            } else {
                navbar.classList.toggle('collapsed');
            }
        },

        // Toggle submenu visibility
        toggleSubmenu(menuItem) {
            const parent = menuItem;
            const isActive = parent.classList.contains('active');

            // Close other open submenus
            const otherItems = document.querySelectorAll('.sidebar-menu > li.active');
            otherItems.forEach(item => {
                if (item !== parent) {
                    item.classList.remove('active');
                }
            });

            // Toggle current menu
            parent.classList.toggle('active', !isActive);
        },

        // Close alert with animation
        closeAlert(alert) {
            alert.classList.add('fade-out');
            setTimeout(() => {
                alert.remove();
            }, this.config.animationDuration);
        },

        // Setup tooltips
        setupTooltips() {
            const tooltipElements = document.querySelectorAll('[data-tooltip]');
            tooltipElements.forEach(element => {
                element.addEventListener('mouseenter', (e) => {
                    this.showTooltip(element);
                });
                element.addEventListener('mouseleave', (e) => {
                    this.hideTooltip(element);
                });
            });
        },

        // Show tooltip
        showTooltip(element) {
            const tooltip = document.createElement('div');
            const text = element.getAttribute('data-tooltip');
            const position = element.getAttribute('data-tooltip-position') || 'top';

            tooltip.className = `tooltip tooltip-${position}`;
            tooltip.textContent = text;
            tooltip.classList.add('fade-in');

            element.appendChild(tooltip);
        },

        // Hide tooltip
        hideTooltip(element) {
            const tooltip = element.querySelector('.tooltip');
            if (tooltip) {
                tooltip.classList.add('fade-out');
                setTimeout(() => {
                    tooltip.remove();
                }, this.config.animationDuration);
            }
        },

        // Setup alert auto-close
        setupAlerts() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const autoClose = alert.getAttribute('data-auto-close');
                if (autoClose) {
                    setTimeout(() => {
                        this.closeAlert(alert);
                    }, parseInt(autoClose) * 1000);
                }
            });
        },

        // Setup form enhancements
        setupForms() {
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                // Add focus class
                input.addEventListener('focus', () => {
                    input.closest('.form-group')?.classList.add('focused');
                });

                input.addEventListener('blur', () => {
                    input.closest('.form-group')?.classList.remove('focused');
                });

                // Add filled class
                if (input.value) {
                    input.closest('.form-group')?.classList.add('filled');
                }

                input.addEventListener('input', () => {
                    input.closest('.form-group')?.classList.toggle('filled', input.value !== '');
                });
            });
        },

        // Utility: Show loading state
        showLoading(element = null) {
            if (!element) {
                element = document.body;
            }

            const loader = document.createElement('div');
            loader.className = 'loading-overlay fade-in';
            loader.innerHTML = '<div class="spinner"></div>';

            element.appendChild(loader);
            return loader;
        },

        // Utility: Hide loading state
        hideLoading(loader) {
            if (loader) {
                loader.classList.add('fade-out');
                setTimeout(() => {
                    loader.remove();
                }, this.config.animationDuration);
            }
        },

        // Utility: Show notification
        showNotification(message, type = 'info', duration = 5000) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} notification-enter`;
            notification.innerHTML = `
                <div class="alert-content">
                    <i class="fas fa-${this.getIconForType(type)}"></i>
                    <div>${message}</div>
                </div>
                <button class="close">&times;</button>
            `;

            document.body.appendChild(notification);

            const closeBtn = notification.querySelector('.close');
            closeBtn.addEventListener('click', () => {
                this.closeAlert(notification);
            });

            if (duration > 0) {
                setTimeout(() => {
                    this.closeAlert(notification);
                }, duration);
            }

            return notification;
        },

        // Get icon for notification type
        getIconForType(type) {
            const icons = {
                success: 'check-circle',
                error: 'exclamation-circle',
                warning: 'exclamation-triangle',
                info: 'info-circle',
            };
            return icons[type] || 'info-circle';
        },

        // Utility: Format currency
        formatCurrency(amount, currency = 'USD') {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency,
                minimumFractionDigits: 2,
            }).format(amount);
        },

        // Utility: Format date
        formatDate(date, format = 'MMM dd, yyyy') {
            if (typeof date === 'string') {
                date = new Date(date);
            }
            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
            }).format(date);
        },

        // Utility: Debounce function
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // Utility: Throttle function
        throttle(func, limit) {
            let inThrottle;
            return function(...args) {
                if (!inThrottle) {
                    func.apply(this, args);
                    inThrottle = true;
                    setTimeout(() => (inThrottle = false), limit);
                }
            };
        },
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            PremiumAdmin.init();
        });
    } else {
        PremiumAdmin.init();
    }

    // Export for external use
    window.PremiumAdmin = PremiumAdmin;
})();

