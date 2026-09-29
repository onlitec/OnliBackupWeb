// assets/js/theme.js - OnliBackup Enterprise Theme Engine (Default White Theme)

(function () {
    'use strict';

    function getStoredTheme() {
        const stored = localStorage.getItem('onlibackup_theme');
        if (stored === 'dark') {
            return 'dark';
        }
        return 'light'; // White theme is the standard default
    }

    function updateToggleElements(theme) {
        document.querySelectorAll('.theme-toggle-btn, .theme-pill-btn').forEach(btn => {
            const icon = btn.querySelector('.theme-toggle-icon, i');
            const text = btn.querySelector('.theme-toggle-text, span');
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

    document.addEventListener('DOMContentLoaded', function () {
        const current = document.documentElement.getAttribute('data-theme') || getStoredTheme();
        applyTheme(current);

        document.querySelectorAll('.theme-toggle-btn, .theme-pill-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                window.toggleTheme();
            });
        });
    });
})();
