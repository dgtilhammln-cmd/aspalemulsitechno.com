document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const sidebar = document.querySelector('[data-sidebar]');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    const userMenus = Array.from(document.querySelectorAll('[data-user-menu]'));

    const closeAllUserMenus = () => {
        userMenus.forEach((menu) => {
            menu.classList.remove('is-open');
            const control = menu.querySelector('[data-user-menu-toggle]');
            if (control) {
                control.setAttribute('aria-expanded', 'false');
            }
        });
    };

    if (userMenus.length > 0) {
        userMenus.forEach((menu) => {
            const control = menu.querySelector('[data-user-menu-toggle]');
            const panel = menu.querySelector('[data-user-menu-panel]');

            if (!control || !panel) {
                return;
            }

            control.setAttribute('aria-expanded', 'false');

            control.addEventListener('click', (event) => {
                event.stopPropagation();
                const isOpen = menu.classList.contains('is-open');
                closeAllUserMenus();
                if (!isOpen) {
                    menu.classList.add('is-open');
                    control.setAttribute('aria-expanded', 'true');
                }
            });

            panel.addEventListener('click', (event) => {
                event.stopPropagation();
            });
        });

        document.addEventListener('click', (event) => {
            if (userMenus.some((menu) => menu.contains(event.target))) {
                return;
            }
            closeAllUserMenus();
        });
    }

    const closeSidebar = () => {
        body.classList.remove('sidebar-open');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    };

    const openSidebar = () => {
        if (!toggle) {
            return;
        }
        body.classList.add('sidebar-open');
        toggle.setAttribute('aria-expanded', 'true');
    };

    const toggleSidebar = () => {
        if (body.classList.contains('sidebar-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    };

    if (toggle) {
        toggle.addEventListener('click', toggleSidebar);
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    const mq = window.matchMedia('(min-width: 961px)');
    const handleMqChange = (event) => {
        if (event.matches) {
            closeSidebar();
        }
    };

    mq.addEventListener('change', handleMqChange);

    if (sidebar) {
        sidebar.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 960) {
                    closeSidebar();
                }
            });
        });
    }

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
            closeAllUserMenus();
        }
    });
});

