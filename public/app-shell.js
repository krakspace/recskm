(() => {
    const root = document.documentElement;
    const narrowViewport = window.matchMedia('(width < 48rem)');
    const openKey = 'sysman.sidebar.open';
    let desktopOpen = readPreference(openKey) !== 'false';
    let mobileOpen = false;

    root.dataset.sidebarOpen = String(!narrowViewport.matches && desktopOpen);
    root.dataset.sidebarReady = '';

    function readPreference(key) {
        try {
            return localStorage.getItem(key);
        } catch {
            return null;
        }
    }

    function savePreference(key, value) {
        try {
            localStorage.setItem(key, value);
        } catch {
            return;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('app-sidebar');
        const toggle = document.getElementById('sidebar-toggle');
        const backdrop = document.querySelector('.sidebar-backdrop');
        const main = document.getElementById('app-main');

        function updateSidebar() {
            const open = narrowViewport.matches ? mobileOpen : desktopOpen;
            const toggleText = open ? 'Tutup sidebar' : 'Buka sidebar';

            root.dataset.sidebarOpen = String(open);
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', toggleText);
            toggle.title = toggleText;
            main.inert = narrowViewport.matches && open;
        }

        function closeMobileSidebar() {
            mobileOpen = false;
            updateSidebar();
            toggle.focus();
        }

        toggle.addEventListener('click', () => {
            if (narrowViewport.matches) {
                mobileOpen = !mobileOpen;
                updateSidebar();

                if (mobileOpen) {
                    const activeLink = sidebar.querySelector('[aria-current="page"]');
                    (activeLink ?? sidebar.querySelector('.sidebar-home')).focus();
                }

                return;
            }

            desktopOpen = !desktopOpen;
            savePreference(openKey, String(desktopOpen));
            updateSidebar();
        });

        backdrop.addEventListener('click', closeMobileSidebar);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && narrowViewport.matches && mobileOpen) {
                closeMobileSidebar();
            }
        });

        narrowViewport.addEventListener('change', () => {
            const focusWasInSidebar = sidebar.contains(document.activeElement);
            mobileOpen = false;
            updateSidebar();

            if (focusWasInSidebar && root.dataset.sidebarOpen === 'false') {
                toggle.focus();
            }
        });

        updateSidebar();
    });
})();
