document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const sidebarToggle = document.getElementById('sidebarToggle');

    if (sidebar && sidebarToggle) {
        // Check for saved sidebar state
        if (localStorage.getItem('sidebar-minimized') === 'true') {
            sidebar.classList.add('minimized');
            if (mainContent) {
                mainContent.classList.add('expanded');
            }
        }

        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('minimized');
            if (mainContent) {
                mainContent.classList.toggle('expanded');
            }
            
            // Save state to localStorage
            if (sidebar.classList.contains('minimized')) {
                localStorage.setItem('sidebar-minimized', 'true');
            } else {
                localStorage.setItem('sidebar-minimized', 'false');
            }
        });
    }
});
