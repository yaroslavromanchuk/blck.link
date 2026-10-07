/**
 * Premium Admin Dashboard - UI Animations
 * Enhanced visual effects and interactions
 */

(function() {
    'use strict';

    const UIAnimations = {
        // Initialize animations
        init() {
            this.setupCardAnimations();
            this.setupTableAnimations();
            this.setupScrollAnimations();
            this.setupButtonAnimations();
            this.setupMenuAnimations();
        },

        // Card entrance animations
        setupCardAnimations() {
            const cards = document.querySelectorAll('.card, .panel, .stat-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    card.classList.add('card-stagger');
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 50);
            });

            // Hover lift effect
            cards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-4px)';
                });

                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0)';
                });
            });
        },

        // Table row animations
        setupTableAnimations() {
            const rows = document.querySelectorAll('table tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-10px)';

                setTimeout(() => {
                    row.style.animation = `slideInLeft 0.3s ease-out forwards`;
                }, index * 30);
            });

            // Row hover effects
            rows.forEach(row => {
                row.addEventListener('mouseenter', () => {
                    row.style.backgroundColor = 'rgba(99, 102, 241, 0.05)';
                    row.style.transform = 'scale(1.01)';
                });

                row.addEventListener('mouseleave', () => {
                    row.style.backgroundColor = 'transparent';
                    row.style.transform = 'scale(1)';
                });
            });
        },

        // Scroll-triggered animations
        setupScrollAnimations() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('slide-in-up');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            document.querySelectorAll('[data-scroll-animate]').forEach(el => {
                observer.observe(el);
            });
        },

        // Button animations
        setupButtonAnimations() {
            const buttons = document.querySelectorAll('.btn');

            buttons.forEach(button => {
                // Ripple effect on click
                button.addEventListener('click', (e) => {
                    const ripple = document.createElement('span');
                    const rect = button.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;

                    ripple.className = 'button-ripple';
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';

                    button.appendChild(ripple);

                    setTimeout(() => ripple.remove(), 600);
                });

                // Hover animations
                button.addEventListener('mouseenter', () => {
                    button.style.transform = 'translateY(-2px)';
                });

                button.addEventListener('mouseleave', () => {
                    button.style.transform = 'translateY(0)';
                });
            });
        },

        // Menu animations
        setupMenuAnimations() {
            const menuItems = document.querySelectorAll('.sidebar-menu > li');

            menuItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-10px)';

                setTimeout(() => {
                    item.style.transition = 'all 0.3s ease-out';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, index * 50);
            });
        },

        // Number counter animation
        animateCounter(element, target, duration = 2000) {
            const start = 0;
            const increment = target / (duration / 16);
            let current = start;

            const counter = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(counter);
                }
                element.textContent = Math.floor(current).toLocaleString();
            }, 16);
        },

        // Progress bar animation
        animateProgressBar(element, percentage, duration = 1000) {
            const startWidth = 0;
            const increment = percentage / (duration / 16);
            let current = startWidth;

            const animate = setInterval(() => {
                current += increment;
                if (current >= percentage) {
                    current = percentage;
                    clearInterval(animate);
                }
                element.style.width = current + '%';
            }, 16);
        },

        // Tooltip animation
        showTooltipAnimated(element, text, position = 'top') {
            const tooltip = document.createElement('div');
            tooltip.className = `tooltip tooltip-${position} fade-in`;
            tooltip.textContent = text;

            document.body.appendChild(tooltip);

            const rect = element.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();

            let top, left;

            switch (position) {
                case 'top':
                    top = rect.top - tooltipRect.height - 10;
                    left = rect.left + rect.width / 2 - tooltipRect.width / 2;
                    break;
                case 'bottom':
                    top = rect.bottom + 10;
                    left = rect.left + rect.width / 2 - tooltipRect.width / 2;
                    break;
                case 'left':
                    top = rect.top + rect.height / 2 - tooltipRect.height / 2;
                    left = rect.left - tooltipRect.width - 10;
                    break;
                case 'right':
                    top = rect.top + rect.height / 2 - tooltipRect.height / 2;
                    left = rect.right + 10;
                    break;
            }

            tooltip.style.top = top + 'px';
            tooltip.style.left = left + 'px';

            return tooltip;
        },

        // Modal animations
        showModalAnimated(modalElement) {
            modalElement.classList.add('show');
            modalElement.style.display = 'block';
            modalElement.classList.add('fade-in');

            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade-in';
            document.body.appendChild(backdrop);

            return backdrop;
        },

        hideModalAnimated(modalElement, backdrop) {
            modalElement.classList.add('fade-out');
            backdrop.classList.add('fade-out');

            setTimeout(() => {
                modalElement.classList.remove('show');
                modalElement.style.display = 'none';
                backdrop.remove();
            }, 300);
        },

        // Pulse effect
        pulseElement(element, duration = 1000, count = 3) {
            element.style.animation = `pulse ${duration / 1000}s ease-in-out ${count}`;
        },

        // Shake effect
        shakeElement(element, intensity = 5, duration = 500) {
            const startX = element.offsetLeft;
            const startTime = Date.now();

            const shake = () => {
                const elapsed = Date.now() - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const angle = Math.random() * Math.PI * 2;
                const distance = intensity * (1 - progress);

                element.style.transform = `translate(${Math.cos(angle) * distance}px, ${Math.sin(angle) * distance}px)`;

                if (progress < 1) {
                    requestAnimationFrame(shake);
                } else {
                    element.style.transform = 'translate(0, 0)';
                }
            };

            shake();
        },

        // Fade in element
        fadeInElement(element, duration = 300) {
            element.style.opacity = '0';
            element.style.transition = `opacity ${duration}ms ease-out`;

            setTimeout(() => {
                element.style.opacity = '1';
            }, 10);
        },

        // Fade out element
        fadeOutElement(element, duration = 300) {
            element.style.transition = `opacity ${duration}ms ease-out`;
            element.style.opacity = '0';

            return new Promise(resolve => {
                setTimeout(resolve, duration);
            });
        },

        // Slide in element
        slideInElement(element, direction = 'left', duration = 300) {
            const translateValue = direction === 'left' ? '-20px' : '20px';

            element.style.opacity = '0';
            element.style.transform = `translateX(${translateValue})`;
            element.style.transition = `all ${duration}ms ease-out`;

            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateX(0)';
            }, 10);
        },

        // Rotate element
        rotateElement(element, degrees = 360, duration = 1000) {
            element.style.transition = `transform ${duration}ms ease-in-out`;
            element.style.transform = `rotate(${degrees}deg)`;
        },

        // Scale element
        scaleElement(element, scale = 1.2, duration = 300) {
            element.style.transition = `transform ${duration}ms ease-out`;
            element.style.transform = `scale(${scale})`;
        },
    };

    // Initialize animations when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            UIAnimations.init();
        });
    } else {
        UIAnimations.init();
    }

    // Export for external use
    window.UIAnimations = UIAnimations;
})();

/* ============================
   CSS for Button Ripple Effect
   ============================ */
const style = document.createElement('style');
style.textContent = `
    .btn {
        position: relative;
        overflow: hidden;
    }

    .button-ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.6);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
    }

    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 999;
    }

    .tooltip {
        position: fixed;
        background-color: rgba(0, 0, 0, 0.9);
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        pointer-events: none;
    }
`;
document.head.appendChild(style);

