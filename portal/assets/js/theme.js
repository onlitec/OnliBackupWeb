// assets/js/theme.js - OnliBackup Universal Theme Engine (White & Dark)

(function () {
    'use strict';

    function getStoredTheme() {
        const stored = localStorage.getItem('onlibackup_theme');
        if (stored === 'light' || stored === 'dark') {
            return stored;
        }
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    function updateToggleElements(theme) {
        document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
            const icon = btn.querySelector('.theme-toggle-icon');
            const text = btn.querySelector('.theme-toggle-text');
            if (theme === 'dark') {
                if (icon) {
                    icon.className = 'theme-toggle-icon fa-solid fa-sun text-warning';
                }
                if (text) {
                    text.textContent = 'Tema Claro';
                }
                btn.setAttribute('title', 'Mudar para Tema Claro');
            } else {
                if (icon) {
                    icon.className = 'theme-toggle-icon fa-solid fa-moon text-primary';
                }
                if (text) {
                    text.textContent = 'Tema Escuro';
                }
                btn.setAttribute('title', 'Mudar para Tema Escuro');
            }
        });
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('onlibackup_theme', theme);
        updateToggleElements(theme);
    }

    window.toggleTheme = function () {
        const current = document.documentElement.getAttribute('data-theme') || 'light';
        const target = current === 'dark' ? 'light' : 'dark';
        applyTheme(target);
    };

    // Apply on DOM load
    document.addEventListener('DOMContentLoaded', function () {
        const current = document.documentElement.getAttribute('data-theme') || getStoredTheme();
        applyTheme(current);

        document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                window.toggleTheme();
            });
        });
    });
})();
