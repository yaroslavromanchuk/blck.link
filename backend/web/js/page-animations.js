/**
 * Safe page-load animations for Gentelella admin layout
 */
(function () {
    'use strict';

    function safeAddClass(selector, className) {
        const el = document.querySelector(selector);
        if (el) {
            el.classList.add(className);
        }
    }

    function initPageAnimations() {
        // Fade in main content after DOM load
        safeAddClass('.main_container', 'page-loaded');
        safeAddClass('.right_col', 'page-loaded');
        safeAddClass('.top_nav', 'page-loaded');

        // Slight delay for content to render naturally
        setTimeout(function () {
            safeAddClass('.left_col', 'page-loaded');
            safeAddClass('.nav_menu', 'page-loaded');
        }, 60);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPageAnimations);
    } else {
        initPageAnimations();
    }
})();

