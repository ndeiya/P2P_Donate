<?php
// Set page title
$page_title = 'Notifications';

// Start session and get user ID
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'database/db_connect.php';

// Start session
start_session();

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Create database instance
$database = new Database();
$db = $database->getConnection();

// Get user ID
$user_id = $_SESSION['user_id'];

// Mark all as read if requested
if (isset($_GET['mark_all_read'])) {
    $query = "UPDATE notifications SET read_status = 1 WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    // Redirect to avoid resubmission
    header("Location: notifications.php");
    exit;
}

// Include header
require_once 'includes/header.php';

// Get notifications
$query = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_OBJ);

// Count unread notifications
$unread_count = count_unread_notifications($user_id, $db);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Notifications</h1>
        <?php if ($unread_count > 0): ?>
            <a href="notifications.php?mark_all_read=1" class="btn btn-sm btn-outline-primary">Mark All as Read</a>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($notifications)): ?>
                <div class="text-center py-5">
                    <p class="mb-0">You don't have any notifications yet.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $notification): ?>
                        <div id="notification-<?php echo $notification->id; ?>" class="notification-item <?php echo $notification->read_status ? '' : 'unread'; ?>" data-id="<?php echo $notification->id; ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo $notification->title; ?></h6>
                                    <p class="mb-1"><?php echo $notification->message; ?></p>
                                    <small class="notification-time"><?php echo format_date($notification->created_at); ?></small>
                                </div>
                                <div>
                                    <?php
                                    switch ($notification->type) {
                                        case 'match':
                                            echo '<span class="badge badge-info">Match</span>';
                                            break;
                                        case 'pledge':
                                            echo '<span class="badge badge-primary">Pledge</span>';
                                            break;
                                        case 'token':
                                            echo '<span class="badge badge-success">Token</span>';
                                            break;
                                        case 'dispute':
                                            echo '<span class="badge badge-danger">Dispute</span>';
                                            break;
                                        case 'system':
                                            echo '<span class="badge badge-secondary">System</span>';
                                            break;
                                        default:
                                            echo '<span class="badge badge-secondary">Other</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Mark notification as read when clicked
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

                // Update notification count in header
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
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>
