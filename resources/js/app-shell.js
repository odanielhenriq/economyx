document.addEventListener('alpine:init', () => {
    Alpine.data('appShell', () => ({
        sidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('economyx_sidebar_collapsed') === 'true',

        toggleSidebarCollapsed() {
            this.sidebarCollapsed = ! this.sidebarCollapsed;
            localStorage.setItem(
                'economyx_sidebar_collapsed',
                this.sidebarCollapsed ? 'true' : 'false',
            );
        },
    }));
});
