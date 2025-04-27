<script>
// Dark mode functionality
function initDarkMode() {
    // Check for saved theme preference or prefer-color-scheme
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        document.body.classList.add('dark-mode');
        if (document.getElementById('dark-mode-toggle')) {
            document.getElementById('dark-mode-toggle').querySelector('i').classList.remove('fa-sun');
            document.getElementById('dark-mode-toggle').querySelector('i').classList.add('fa-moon');
        }
    } else {
        document.body.classList.remove('dark-mode');
        if (document.getElementById('dark-mode-toggle')) {
            document.getElementById('dark-mode-toggle').querySelector('i').classList.remove('fa-moon');
            document.getElementById('dark-mode-toggle').querySelector('i').classList.add('fa-sun');
        }
    }
}

// Initialize dark mode
document.addEventListener('DOMContentLoaded', function() {
    initDarkMode();

    // Toggle dark mode
    if (document.getElementById('dark-mode-toggle')) {
        document.getElementById('dark-mode-toggle').addEventListener('click', function() {
            if (document.body.classList.contains('dark-mode')) {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
                this.querySelector('i').classList.remove('fa-moon');
                this.querySelector('i').classList.add('fa-sun');
            } else {
                document.body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
                this.querySelector('i').classList.remove('fa-sun');
                this.querySelector('i').classList.add('fa-moon');
            }
        });
    }

    // Auto-hide flash messages after 60 seconds (except payment instructions)
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-info.payment-instructions)');
        alerts.forEach(function(alert) {
            if (alert && typeof alert.style !== 'undefined') {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert.parentNode) {
                        alert.style.display = 'none';
                    }
                }, 500);
            }
        });
    }, 60000);
});
</script>
