/* ==================================================
   THEME TOGGLE - BELAJARYUK
   ================================================== */

(function() {
    'use strict';

    const THEME_KEY = 'belajaryuk_theme';

    function getPreferredTheme() {
        const stored = localStorage.getItem(THEME_KEY);
        if (stored) return stored;
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(THEME_KEY, theme);
    }

    function toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme') || 'light';
        const next = current === 'dark' ? 'light' : 'dark';
        setTheme(next);
    }

    // Apply theme immediately
    setTheme(getPreferredTheme());

    // Export for use by other scripts
    window.toggleTheme = toggleTheme;
    window.setTheme = setTheme;
})();
