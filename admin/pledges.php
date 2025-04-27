<?php
// Set page title
$page_title = 'Pledges & Matches';

// Include configuration
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../database/db_connect.php';

// Start session
start_session();

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

// Create database instance
$database = new Database();
$db = $database->getConnection();

// Get user ID
$user_id = $_SESSION['user_id'];

// Initialize variables
$success_message = '';
$error_message = '';

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        case 'match':
            // Match pledges manually
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['match_pledges'])) {
                $sender_id = $_POST['sender_id'];
                $receiver_id = $_POST['receiver_id'];

                // Get sender pledge
                $query = "SELECT * FROM pledges WHERE user_id = :user_id AND status = 'pending'";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $sender_id);
                $stmt->execute();
                $sender_pledge = $stmt->fetch(PDO::FETCH_OBJ);

                if ($sender_pledge) {
                    // Start transaction
                    $db->beginTransaction();

                    try {
                        // Update pledge status
                        $query = "UPDATE pledges SET status = 'matched' WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':id', $sender_pledge->id);
                        $stmt->execute();

                        // Set deadline (48 hours from now)
                        $deadline = date('Y-m-d H:i:s', strtotime('+48 hours'));

                        // Create match
                        $query = "INSERT INTO matches (pledge_id, sender_id, receiver_id, amount, deadline) VALUES (:pledge_id, :sender_id, :receiver_id, :amount, :deadline)";
                        $stmt = $db->prepare($query);

                        $stmt->bindParam(':pledge_id', $sender_pledge->id);
                        $stmt->bindParam(':sender_id', $sender_id);
                        $stmt->bindParam(':receiver_id', $receiver_id);
                        $stmt->bindParam(':amount', $sender_pledge->amount);
                        $stmt->bindParam(':deadline', $deadline);

                        $stmt->execute();
                        $match_id = $db->lastInsertId();

                        // Create notifications
                        create_notification($sender_id, 'Match Created', 'You have been matched with a receiver for your pledge of ' . format_currency($sender_pledge->amount, 'Tokens') . '.', 'match', $db);
                        create_notification($receiver_id, 'Match Created', 'You have been matched with a sender for a donation of ' . format_currency($sender_pledge->amount, 'Tokens') . '.', 'match', $db);

                        // Commit transaction
                        $db->commit();

                        // Set success message
                        $success_message = 'Pledges matched successfully.';
                    } catch (Exception $e) {
                        // Rollback transaction
                        $db->rollBack();

                        // Set error message
                        $error_message = 'Failed to match pledges. Error: ' . $e->getMessage();
                    }
                } else {
                    $error_message = 'No pending pledge found for the selected sender.';
                }
            }
            break;

        case 'cancel_match':
            // Cancel match
            if (isset($_GET['id'])) {
                $match_id = $_GET['id'];

                // Get match details
                $query = "SELECT * FROM matches WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $match_id);
                $stmt->execute();
                $match = $stmt->fetch(PDO::FETCH_OBJ);

                if ($match && ($match->status == 'pending' || $match->status == 'payment_sent')) {
                    // Start transaction
                    $db->beginTransaction();

                    try {
                        // Update match status
                        $query = "UPDATE matches SET status = 'cancelled' WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':id', $match_id);
                        $stmt->execute();

                        // Update pledge status
                        $query = "UPDATE pledges SET status = 'pending' WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':id', $match->pledge_id);
                        $stmt->execute();

                        // Create notifications
                        create_notification($match->sender_id, 'Match Cancelled', 'Your match #' . $match_id . ' has been cancelled by an administrator.', 'match', $db);
                        create_notification($match->receiver_id, 'Match Cancelled', 'Your match #' . $match_id . ' has been cancelled by an administrator.', 'match', $db);

                        // Commit transaction
                        $db->commit();

                        // Set success message
                        $success_message = 'Match cancelled successfully.';
                    } catch (Exception $e) {
                        // Rollback transaction
                        $db->rollBack();

                        // Set error message
                        $error_message = 'Failed to cancel match. Error: ' . $e->getMessage();
                    }
                } else {
                    $error_message = 'Invalid match or match cannot be cancelled.';
                }
            }
            break;

        case 'cancel_pledge':
            // Cancel pledge
            if (isset($_GET['id'])) {
                $pledge_id = $_GET['id'];

                // Get pledge details
                $query = "SELECT * FROM pledges WHERE id = :id AND status = 'pending'";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $pledge_id);
                $stmt->execute();
                $pledge = $stmt->fetch(PDO::FETCH_OBJ);

                if ($pledge) {
                    // Start transaction
                    $db->beginTransaction();

                    try {
                        // Update pledge status
                        $query = "UPDATE pledges SET status = 'cancelled' WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':id', $pledge_id);
                        $stmt->execute();

                        // Refund platform fee (10 tokens) to user
                        $platform_fee = 10;
                        $query = "UPDATE users SET token_balance = token_balance + :amount WHERE id = :user_id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':amount', $platform_fee);
                        $stmt->bindParam(':user_id', $pledge->user_id);
                        $stmt->execute();

                        // Record token transaction
                        $query = "INSERT INTO tokens (user_id, amount, transaction_type, status, reference) VALUES (:user_id, :amount, 'refund', 'confirmed', 'Platform fee refund for admin cancelled pledge')";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':user_id', $pledge->user_id);
                        $stmt->bindParam(':amount', $platform_fee);
                        $stmt->execute();

                        // Create notification
                        create_notification($pledge->user_id, 'Pledge Cancelled', 'Your pledge of GHS ' . $pledge->amount . ' has been cancelled by an administrator and the platform fee of 10 tokens has been refunded.', 'pledge', $db);

                        // Commit transaction
                        $db->commit();

                        // Set success message
                        $success_message = 'Pledge cancelled successfully and tokens refunded.';
                    } catch (Exception $e) {
                        // Rollback transaction
                        $db->rollBack();

                        // Set error message
                        $error_message = 'Failed to cancel pledge. Error: ' . $e->getMessage();
                    }
                } else {
                    $error_message = 'Invalid pledge or pledge cannot be cancelled.';
                }
            }
            break;
    }
}

// Get pending pledges
$query = "SELECT p.*, u.name as user_name, u.email, u.mobile_number, u.mobile_name
          FROM pledges p
          JOIN users u ON p.user_id = u.id
          WHERE p.status = 'pending'
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$pending_pledges = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get active matches
$query = "SELECT m.*,
          p.amount,
          sender.name as sender_name,
          sender.email as sender_email,
          sender.mobile_number as sender_mobile,
          receiver.name as receiver_name,
          receiver.email as receiver_email,
          receiver.mobile_number as receiver_mobile
          FROM matches m
          JOIN pledges p ON m.pledge_id = p.id
          JOIN users sender ON m.sender_id = sender.id
          JOIN users receiver ON m.receiver_id = receiver.id
          WHERE m.status IN ('pending', 'payment_sent')
          ORDER BY m.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$active_matches = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get completed/cancelled matches
$query = "SELECT m.*,
          p.amount,
          sender.name as sender_name,
          receiver.name as receiver_name
          FROM matches m
          JOIN pledges p ON m.pledge_id = p.id
          JOIN users sender ON m.sender_id = sender.id
          JOIN users receiver ON m.receiver_id = receiver.id
          WHERE m.status IN ('completed', 'cancelled', 'disputed')
          ORDER BY m.updated_at DESC
          LIMIT 50";
$stmt = $db->prepare($query);
$stmt->execute();
$completed_matches = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get users for matching
$query = "SELECT id, name, email, mobile_number, token_balance FROM users WHERE status = 'active' ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-dark-mode.css">
</head>
<body>
    <?php include 'includes/admin_navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/admin_sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Pledges & Matches</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="pledges.php?action=match" class="btn btn-sm btn-primary">
                            <i class="fas fa-exchange-alt"></i> Match Pledges
                        </a>
                    </div>
                </div>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['action']) && $_GET['action'] == 'match'): ?>
                    <!-- Match Pledges Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Match Pledges Manually</h5>
                        </div>
                        <div class="card-body">
                            <form action="pledges.php?action=match" method="post" id="match-pledges-form">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sender_id">Sender (User with Pledge)</label>
                                            <select name="sender_id" class="form-control" required>
                                                <option value="">-- Select Sender --</option>
                                                <?php foreach ($users as $user): ?>
                                                    <?php
                                                    // Check if user has pending pledges
                                                    $query = "SELECT COUNT(*) as count FROM pledges WHERE user_id = :user_id AND status = 'pending'";
                                                    $stmt = $db->prepare($query);
                                                    $stmt->bindParam(':user_id', $user->id);
                                                    $stmt->execute();
                                                    $result = $stmt->fetch(PDO::FETCH_OBJ);
                                                    $has_pledges = $result->count > 0;

                                                    if ($has_pledges):
                                                    ?>
                                                        <option value="<?php echo $user->id; ?>"><?php echo $user->name; ?> (<?php echo $user->email; ?>)</option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="receiver_id">Receiver</label>
                                            <select name="receiver_id" class="form-control" required>
                                                <option value="">-- Select Receiver --</option>
                                                <?php foreach ($users as $user): ?>
                                                    <option value="<?php echo $user->id; ?>"><?php echo $user->name; ?> (<?php echo $user->email; ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="submit" name="match_pledges" class="btn btn-primary">Match Pledges</button>
                                    <a href="pledges.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Pending Pledges -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Pending Pledges</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_pledges)): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">No pending pledges found.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_pledges as $pledge): ?>
                                            <tr>
                                                <td>#<?php echo $pledge->id; ?></td>
                                                <td>
                                                    <?php echo $pledge->user_name; ?><br>
                                                    <small><?php echo $pledge->email; ?></small>
                                                </td>
                                                <td><?php echo format_currency($pledge->amount, 'Tokens'); ?></td>
                                                <td><?php echo format_date($pledge->created_at); ?></td>
                                                <td>
                                                    <a href="pledges.php?action=cancel_pledge&id=<?php echo $pledge->id; ?>" class="btn btn-sm btn-danger confirm-cancel-pledge">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Active Matches -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Active Matches</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($active_matches)): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">No active matches found.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Sender</th>
                                            <th>Receiver</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Deadline</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($active_matches as $match): ?>
                                            <tr>
                                                <td>#<?php echo $match->id; ?></td>
                                                <td>
                                                    <?php echo $match->sender_name; ?><br>
                                                    <small><?php echo $match->sender_email; ?></small>
                                                </td>
                                                <td>
                                                    <?php echo $match->receiver_name; ?><br>
                                                    <small><?php echo $match->receiver_email; ?></small>
                                                </td>
                                                <td><?php echo format_currency($match->amount, 'Tokens'); ?></td>
                                                <td>
                                                    <?php
                                                    switch ($match->status) {
                                                        case 'pending':
                                                            echo '<span class="badge badge-warning">Pending</span>';
                                                            break;
                                                        case 'payment_sent':
                                                            echo '<span class="badge badge-info">Payment Sent</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="badge badge-secondary">Unknown</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $deadline = new DateTime($match->deadline);
                                                    $now = new DateTime();
                                                    $interval = $now->diff($deadline);
                                                    $hours_remaining = ($interval->days * 24) + $interval->h;

                                                    echo format_date($match->deadline);

                                                    if ($deadline > $now) {
                                                        echo ' <span class="badge badge-warning">' . $hours_remaining . ' hours remaining</span>';
                                                    } else {
                                                        echo ' <span class="badge badge-danger">Expired</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <a href="pledges.php?action=cancel_match&id=<?php echo $match->id; ?>" class="btn btn-sm btn-danger confirm-cancel-match">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Completed/Cancelled Matches -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Completed/Cancelled Matches</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($completed_matches)): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">No completed or cancelled matches found.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Sender</th>
                                            <th>Receiver</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($completed_matches as $match): ?>
                                            <tr>
                                                <td>#<?php echo $match->id; ?></td>
                                                <td><?php echo $match->sender_name; ?></td>
                                                <td><?php echo $match->receiver_name; ?></td>
                                                <td><?php echo format_currency($match->amount, 'Tokens'); ?></td>
                                                <td>
                                                    <?php
                                                    switch ($match->status) {
                                                        case 'completed':
                                                            echo '<span class="badge badge-success">Completed</span>';
                                                            break;
                                                        case 'cancelled':
                                                            echo '<span class="badge badge-secondary">Cancelled</span>';
                                                            break;
                                                        case 'disputed':
                                                            echo '<span class="badge badge-danger">Disputed</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="badge badge-secondary">Unknown</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo format_date($match->updated_at); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <?php include 'includes/dark_mode_script.php'; ?>
    <script src="../assets/js/admin.js"></script>

    <script>
    $(document).ready(function() {
        // Confirm cancel pledge
        $('.confirm-cancel-pledge').on('click', function(e) {
            if (!confirm('Are you sure you want to cancel this pledge? The tokens will be refunded to the user.')) {
                e.preventDefault();
            }
        });

        // Confirm cancel match
        $('.confirm-cancel-match').on('click', function(e) {
            if (!confirm('Are you sure you want to cancel this match? The pledge will be returned to pending status.')) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>
