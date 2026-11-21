import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Theme toggle & UI interactions
document.addEventListener('DOMContentLoaded', () => {
    const root = document.documentElement;
    const toggle = document.querySelector('[data-theme-toggle]');
    const storageKey = 'stark-garage-theme';

    const notifyThemeChange = (theme) => {
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
    };

    const applyTheme = (theme) => {
        root.setAttribute('data-bs-theme', theme);
        if (toggle) {
            const icon = toggle.querySelector('i');
            if (icon) {
                icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
            }
        }
        notifyThemeChange(theme);
    };

    const stored = localStorage.getItem(storageKey);
    if (stored === 'dark' || stored === 'light') {
        applyTheme(stored);
    } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        applyTheme('dark');
    }

    if (toggle) {
        toggle.addEventListener('click', () => {
            const current = root.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            localStorage.setItem(storageKey, next);
            applyTheme(next);
        });
    }

    // Sidebar Mobile Toggle
    const sidebar = document.getElementById('appSidebar');
    const toggles = document.querySelectorAll('[data-sidebar-toggle]');
    const overlay = document.querySelector('.sidebar-overlay');

    const closeSidebar = () => {
        sidebar?.classList.remove('show');
        overlay?.classList.remove('show');
        document.body.classList.remove('overflow-hidden');
    };

    const openSidebar = () => {
        sidebar?.classList.add('show');
        overlay?.classList.add('show');
        document.body.classList.add('overflow-hidden');
    };

    toggles.forEach((btn) => {
        btn.addEventListener('click', () => {
            if (sidebar?.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    });

    overlay?.addEventListener('click', closeSidebar);

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) {
            closeSidebar();
        }
    });

    // Quick Command Bar shortcut (Ctrl+K or Cmd+K)
    window.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('globalQuickSearch');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
    });

    // Dropdown toggle handler
    document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const parent = trigger.closest('.dropdown');
            const menu = parent?.querySelector('.dropdown-menu');
            if (menu) {
                const isOpen = menu.classList.contains('show');
                document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
                if (!isOpen) {
                    menu.classList.add('show');
                }
            }
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
    });
});
