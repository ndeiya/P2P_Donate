// Document Ready
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Initialize clipboard.js
    if (typeof ClipboardJS !== 'undefined') {
        var clipboard = new ClipboardJS('.copy-btn');

        clipboard.on('success', function(e) {
            var $btn = $(e.trigger);
            $btn.html('<i class="fas fa-check"></i>');
            setTimeout(function() {
                $btn.html('<i class="fas fa-copy"></i>');
            }, 2000);
            e.clearSelection();
        });
    }

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

    // Initialize popovers
    $('[data-toggle="popover"]').popover();

    // Auto-hide flash messages after 60 seconds (except payment instructions)
    setTimeout(function() {
        $('.alert:not(.alert-info.payment-instructions)').fadeOut('slow');
    }, 60000);

    // Confirm delete actions
    $('.confirm-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });

    // Confirm pledge action
    $('#make-pledge-btn').on('click', function(e) {
        e.preventDefault();
        $('#pledge-modal').modal('show');
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

    // Chat auto-scroll to bottom
    var chatContainer = $('.chat-container');
    if (chatContainer.length) {
        chatContainer.scrollTop(chatContainer[0].scrollHeight);
    }

    // Mark notification as read
    $('.notification-item').on('click', function() {
        var notificationId = $(this).data('id');

        $.ajax({
            url: 'controllers/notification_controller.php',
            type: 'POST',
            data: {
                action: 'mark_as_read',
                notification_id: notificationId
            },
            success: function(response) {
                // Remove unread class
                $('#notification-' + notificationId).removeClass('unread');

                // Update notification count
                var count = parseInt($('.notification-badge').text());
                if (count > 0) {
                    count--;
                    if (count === 0) {
                        $('.notification-badge').hide();
                    } else {
                        $('.notification-badge').text(count);
                    }
                }
            }
        });
    });

    // Token purchase amount calculation
    $('#token-amount').on('input', function() {
        var amount = parseFloat($(this).val()) || 0;
        var rate = parseFloat($('#token-rate').val()) || 0.1; // Default to 0.1 (1 USDT = 10 tokens)
        var total = amount * rate;

        $('#usdt-amount').val(total.toFixed(2));
    });

    // Initialize USDT amount on page load
    $(function() {
        var amount = parseFloat($('#token-amount').val()) || 0;
        var rate = parseFloat($('#token-rate').val()) || 0.1;
        var total = amount * rate;

        $('#usdt-amount').val(total.toFixed(2));
    });

    // Mobile sidebar toggle
    $('#sidebar-toggle').on('click', function() {
        $('#sidebar').toggleClass('show');
        $('body').toggleClass('sidebar-open');
    });

    // Close sidebar when clicking outside on mobile
    $(document).on('click', function(e) {
        if ($(window).width() < 992) {
            if (!$(e.target).closest('#sidebar').length && !$(e.target).closest('#sidebar-toggle').length) {
                $('#sidebar').removeClass('show');
                $('body').removeClass('sidebar-open');
            }
        }
    });

    // Add data-label attributes to table cells for mobile responsive tables
    $('.table-mobile-responsive').each(function() {
        var headers = [];
        $(this).find('thead th').each(function() {
            headers.push($(this).text());
        });

        $(this).find('tbody tr').each(function() {
            $(this).find('td').each(function(i) {
                $(this).attr('data-label', headers[i]);
            });
        });
    });

    // Adjust textarea height automatically
    $('textarea').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});
