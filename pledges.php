<?php
// Set page title
$page_title = 'Pledges';

// Include header
require_once 'includes/header.php';

// Include pledge system functions
require_once 'includes/pledge_system.php';

// Get user token balance
$token_balance = get_token_balance($user_id, $db);

// Get user data including pledges to receive and mobile money details
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_OBJ);
$pledges_to_receive = $user->pledges_to_receive ?? 0;

// Check if user is eligible to make a pledge
$is_eligible = is_eligible_to_pledge($db, $user_id);

// Process make pledge form
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['make_pledge'])) {
    // Check if user is eligible to make a pledge
    if (!$is_eligible) {
        $error_message = 'You already have an active pledge. Please wait until it is completed before making a new pledge.';
    }
    // Check if user has mobile money details
    elseif (empty($_POST['mobile_number']) || empty($_POST['mobile_name'])) {
        $error_message = 'Please provide your Mobile Money Number and Name.';
    }
    // Validate mobile money details
    elseif (strlen($_POST['mobile_number']) < 10) {
        $error_message = 'Please enter a valid mobile money number.';
    }
    else {
        // Save mobile money details
        $mobile_number = sanitize($_POST['mobile_number']);
        $mobile_name = sanitize($_POST['mobile_name']);

        // Update user's mobile money details
        $query = "UPDATE users SET mobile_number = :mobile_number, mobile_name = :mobile_name WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':mobile_number', $mobile_number);
        $stmt->bindParam(':mobile_name', $mobile_name);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        // Create the pledge
        $result = create_pledge($db, $user_id);

        if ($result['status']) {
            // Try to match the pledge immediately
            $match_result = match_pledge($db, $result['pledge_id']);

            if ($match_result['status']) {
                $success_message = 'Your pledge has been created and matched successfully! Please check your matches to see who you should send payment to.';
            } else {
                $success_message = 'Your pledge has been created successfully and will be matched soon.';
            }
        } else {
            $error_message = $result['message'];
        }
    }
}

// Get active pledges
$query = "SELECT * FROM pledges WHERE user_id = :user_id AND status IN ('pending', 'matched') ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$active_pledges = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get pledge history
$query = "SELECT * FROM pledges WHERE user_id = :user_id AND status IN ('completed', 'timeout', 'cancelled') ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$pledge_history = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Pledges</h1>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <!-- Make a Pledge Block -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Make a Pledge</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <?php if ($pledges_to_receive > 0): ?>
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check-circle"></i> You are in the queue!</h6>
                            <p>You will receive <strong><?php echo $pledges_to_receive; ?></strong> more pledge(s) of GHS <?php echo PLEDGE_AMOUNT; ?> each.</p>
                        </div>
                    <?php endif; ?>

                    <p>By making a pledge of <strong>GHS <?php echo PLEDGE_AMOUNT; ?></strong>, you'll be placed in queue to receive <strong>two pledges</strong> in return!</p>

                    <?php if ($is_eligible): ?>
                        <button id="make-pledge-btn" class="btn btn-primary">Make a Pledge</button>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <p>You already have an active pledge. Please wait until it is completed before making a new pledge.</p>
                        </div>
                        <button class="btn btn-primary" disabled>Make a Pledge</button>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-info">
                        <h6>How Our Pledge System Works:</h6>
                        <ol>
                            <li>You pay a platform fee of <strong>10 tokens</strong> to make a pledge.</li>
                            <li>You pledge to send <strong>GHS <?php echo PLEDGE_AMOUNT; ?></strong> to another user.</li>
                            <li>The system matches you with a receiver.</li>
                            <li>You send the GHS <?php echo PLEDGE_AMOUNT; ?> payment via Mobile Money directly to the receiver.</li>
                            <li>The receiver confirms receipt of payment.</li>
                            <li>You are placed in queue to receive <strong>two pledges</strong> of GHS <?php echo PLEDGE_AMOUNT; ?> each.</li>
                            <li>After receiving two pledges, you can make another pledge to rejoin the queue.</li>
                        </ol>
                        <p class="mb-0 mt-2"><small>Note: The GHS <?php echo PLEDGE_AMOUNT; ?> payment happens offline between users. The platform only charges 10 tokens as a fee.</small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Pledges -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Active Pledges</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($active_pledges)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No active pledges found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($active_pledges as $pledge): ?>
                                <tr>
                                    <td><?php echo format_date($pledge->created_at); ?></td>
                                    <td><?php echo format_currency($pledge->amount, $pledge->currency ?? 'GHS'); ?></td>
                                    <td>
                                        <?php
                                        switch ($pledge->status) {
                                            case 'pending':
                                                echo '<span class="badge badge-warning">Pending Match</span>';
                                                break;
                                            case 'matched':
                                                echo '<span class="badge badge-info">Matched</span>';
                                                break;
                                            default:
                                                echo '<span class="badge badge-secondary">Unknown</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($pledge->status == 'matched'): ?>
                                            <a href="matches.php" class="btn btn-sm btn-primary">View Match</a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-danger cancel-pledge" data-id="<?php echo $pledge->id; ?>">Cancel</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pledge History -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Pledge History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Completed Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pledge_history)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No pledge history found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pledge_history as $pledge): ?>
                                <tr>
                                    <td><?php echo format_date($pledge->created_at); ?></td>
                                    <td><?php echo format_currency($pledge->amount, $pledge->currency ?? 'GHS'); ?></td>
                                    <td>
                                        <?php
                                        switch ($pledge->status) {
                                            case 'completed':
                                                echo '<span class="badge badge-success">Completed</span>';
                                                break;
                                            case 'timeout':
                                                echo '<span class="badge badge-danger">Timeout</span>';
                                                break;
                                            case 'cancelled':
                                                echo '<span class="badge badge-secondary">Cancelled</span>';
                                                break;
                                            default:
                                                echo '<span class="badge badge-secondary">Unknown</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo format_date($pledge->updated_at); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Make Pledge Modal -->
<div class="modal fade" id="pledge-modal" tabindex="-1" role="dialog" aria-labelledby="pledgeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pledgeModalLabel">Make a Pledge</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <p class="mb-0">You are about to make a pledge of <strong>GHS <?php echo PLEDGE_AMOUNT; ?></strong>.</p>
                        <p class="mb-0 mt-2"><strong>Platform Fee:</strong> 10 tokens will be deducted from your account.</p>
                    </div>

                    <div class="form-group">
                        <label for="mobile_number">Mobile Money Number <span class="text-danger">*</span></label>
                        <input type="text" name="mobile_number" class="form-control" value="<?php echo $user->mobile_number ?? ''; ?>" required>
                        <small class="form-text text-muted">Enter the mobile money number where you will receive payments.</small>
                    </div>

                    <div class="form-group">
                        <label for="mobile_name">Mobile Money Name <span class="text-danger">*</span></label>
                        <input type="text" name="mobile_name" class="form-control" value="<?php echo $user->mobile_name ?? ''; ?>" required>
                        <small class="form-text text-muted">Enter the name registered with your mobile money account.</small>
                    </div>

                    <div class="alert alert-warning">
                        <p class="mb-0"><strong>Important:</strong> By making a pledge, you agree to:</p>
                        <ul class="mb-0 mt-2">
                            <li>Pay a platform fee of 10 tokens (non-refundable unless pledge is cancelled)</li>
                            <li>Send GHS <?php echo PLEDGE_AMOUNT; ?> directly to the matched receiver via Mobile Money</li>
                            <li>Complete the payment within 48 hours of being matched</li>
                            <li>Provide proof of payment when requested</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="make_pledge" class="btn btn-primary">Confirm Pledge</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Show modal when button is clicked
    $('#make-pledge-btn').on('click', function() {
        $('#pledge-modal').modal('show');
    });

    // Show error in modal if exists
    <?php if (!empty($error_message)): ?>
        $('#pledge-modal').modal('show');
    <?php endif; ?>

    // Cancel pledge
    $('.cancel-pledge').on('click', function() {
        if (confirm('Are you sure you want to cancel this pledge? Your platform fee of 10 tokens will be refunded.')) {
            var pledgeId = $(this).data('id');
            window.location.href = 'controllers/pledge_controller.php?action=cancel&id=' + pledgeId;
        }
    });
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>
