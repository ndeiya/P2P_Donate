// Document Ready
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Initialize popovers
    $('[data-toggle="popover"]').popover();

    // Dark mode functionality
    function initDarkMode() {
        // Check for saved theme preference or prefer-color-scheme
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            document.body.classList.add('dark-mode');
            $('#dark-mode-toggle i').removeClass('fa-sun').addClass('fa-moon');
        } else {
            document.body.classList.remove('dark-mode');
            $('#dark-mode-toggle i').removeClass('fa-moon').addClass('fa-sun');
        }
    }

    // Initialize dark mode
    initDarkMode();

    // Toggle dark mode
    $('#dark-mode-toggle').on('click', function() {
        if (document.body.classList.contains('dark-mode')) {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');
            $('#dark-mode-toggle i').removeClass('fa-moon').addClass('fa-sun');
        } else {
            document.body.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark');
            $('#dark-mode-toggle i').removeClass('fa-sun').addClass('fa-moon');
        }
    });

    // Auto-hide flash messages after 60 seconds (except payment instructions)
    setTimeout(function() {
        $('.alert:not(.alert-info.payment-instructions)').fadeOut('slow');
    }, 60000);

    // Confirm delete actions
    $('.confirm-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
        }
    });

    // Confirm block user
    $('.confirm-block').on('click', function(e) {
        if (!confirm('Are you sure you want to block this user?')) {
            e.preventDefault();
        }
    });

    // Confirm unblock user
    $('.confirm-unblock').on('click', function(e) {
        if (!confirm('Are you sure you want to unblock this user?')) {
            e.preventDefault();
        }
    });

    // Confirm token approval
    $('.confirm-approve').on('click', function(e) {
        if (!confirm('Are you sure you want to approve this token purchase?')) {
            e.preventDefault();
        }
    });

    // Confirm token rejection
    $('.confirm-reject').on('click', function(e) {
        if (!confirm('Are you sure you want to reject this token purchase?')) {
            e.preventDefault();
        }
    });

    // File input preview
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);

        // Preview image if it's an image file
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#image-preview').attr('src', e.target.result).show();
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Token credit amount calculation
    $('#token-amount').on('input', function() {
        var amount = parseFloat($(this).val()) || 0;
        var rate = parseFloat($('#token-rate').val()) || 1;
        var total = amount * rate;

        $('#usdt-amount').val(total.toFixed(2));
    });

    // Match pledges manually
    $('#match-pledges-form').on('submit', function(e) {
        if (!confirm('Are you sure you want to match these pledges?')) {
            e.preventDefault();
        }
    });

    // Resolve dispute
    $('#resolve-dispute-form').on('submit', function(e) {
        if (!confirm('Are you sure you want to resolve this dispute with the selected action?')) {
            e.preventDefault();
        }
    });

    // Toggle all checkboxes
    $('#select-all').on('change', function() {
        $('.item-checkbox').prop('checked', $(this).prop('checked'));
    });

    // Check if any checkbox is checked
    $('.item-checkbox').on('change', function() {
        if ($('.item-checkbox:checked').length === $('.item-checkbox').length) {
            $('#select-all').prop('checked', true);
        } else {
            $('#select-all').prop('checked', false);
        }
    });

    // Bulk action confirmation
    $('#bulk-action-form').on('submit', function(e) {
        var action = $('#bulk-action').val();
        var checkedCount = $('.item-checkbox:checked').length;

        if (checkedCount === 0) {
            alert('Please select at least one item.');
            e.preventDefault();
            return;
        }

        if (action === '') {
            alert('Please select an action.');
            e.preventDefault();
            return;
        }

        if (!confirm('Are you sure you want to ' + action + ' the selected items?')) {
            e.preventDefault();
        }
    });
});
