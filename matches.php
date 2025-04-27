<?php
// Set page title
$page_title = 'Matches';

// Include header
require_once 'includes/header.php';

// Process upload proof form
$transaction_id = '';
$transaction_id_err = $file_err = '';
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_proof'])) {
    // Validate transaction ID
    if (empty(trim($_POST['transaction_id']))) {
        $transaction_id_err = 'Please enter a transaction ID.';
    } else {
        $transaction_id = sanitize($_POST['transaction_id']);
    }

    // Validate file upload
    if (!isset($_FILES['proof_file']) || $_FILES['proof_file']['error'] == UPLOAD_ERR_NO_FILE) {
        $file_err = 'Please upload proof of payment.';
    } else {
        $upload_result = upload_file($_FILES['proof_file'], 'uploads/proofs/');

        if (!$upload_result['success']) {
            $file_err = $upload_result['message'];
        }
    }

    // If no errors, process the upload
    if (empty($transaction_id_err) && empty($file_err)) {
        // Get match ID
        $match_id = $_POST['match_id'];

        // Update match
        $query = "UPDATE matches SET status = 'payment_sent', proof_file = :proof_file, transaction_id = :transaction_id, updated_at = NOW() WHERE id = :id AND sender_id = :user_id AND status = 'pending'";
        $stmt = $db->prepare($query);

        $stmt->bindParam(':proof_file', $upload_result['filename']);
        $stmt->bindParam(':transaction_id', $transaction_id);
        $stmt->bindParam(':id', $match_id);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
            // Get match details
            $query = "SELECT * FROM matches WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $match_id);
            $stmt->execute();
            $match = $stmt->fetch(PDO::FETCH_OBJ);

            // Create notification for sender
            create_notification($user_id, 'Payment Sent', 'You have sent payment for match #' . $match_id . '.', 'match', $db);

            // Create notification for receiver
            create_notification($match->receiver_id, 'Payment Received', 'You have received payment for match #' . $match_id . '. Please confirm receipt.', 'match', $db);

            // Set success message
            $success_message = 'Your payment proof has been uploaded successfully.';

            // Clear form data
            $transaction_id = '';
        } else {
            $error_message = 'Something went wrong. Please try again.';
        }
    }
}

// Process confirm receipt form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_receipt'])) {
    // Get match ID
    $match_id = $_POST['match_id'];

    // Update match
    $query = "UPDATE matches SET status = 'completed', updated_at = NOW() WHERE id = :id AND receiver_id = :user_id AND status = 'payment_sent'";
    $stmt = $db->prepare($query);

    $stmt->bindParam(':id', $match_id);
    $stmt->bindParam(':user_id', $user_id);

    if ($stmt->execute()) {
        // Get match details
        $query = "SELECT * FROM matches WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $match_id);
        $stmt->execute();
        $match = $stmt->fetch(PDO::FETCH_OBJ);

        // Update pledge status
        $query = "UPDATE pledges SET status = 'completed', updated_at = NOW() WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $match->pledge_id);
        $stmt->execute();

        // Create notification for sender
        create_notification($match->sender_id, 'Payment Confirmed', 'Your payment for match #' . $match_id . ' has been confirmed by the receiver.', 'match', $db);

        // Create notification for receiver
        create_notification($user_id, 'Receipt Confirmed', 'You have confirmed receipt of payment for match #' . $match_id . '.', 'match', $db);

        // Set success message
        $success_message = 'You have confirmed receipt of payment.';
    } else {
        $error_message = 'Something went wrong. Please try again.';
    }
}

// Get current matches as sender
$query = "SELECT m.*, p.amount, u.name as receiver_name, u.mobile_number, u.mobile_name
          FROM matches m
          JOIN pledges p ON m.pledge_id = p.id
          JOIN users u ON m.receiver_id = u.id
          WHERE m.sender_id = :user_id AND m.status IN ('pending', 'payment_sent')
          ORDER BY m.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$sender_matches = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get current matches as receiver
$query = "SELECT m.*, p.amount, u.name as sender_name
          FROM matches m
          JOIN pledges p ON m.pledge_id = p.id
          JOIN users u ON m.sender_id = u.id
          WHERE m.receiver_id = :user_id AND m.status IN ('pending', 'payment_sent')
          ORDER BY m.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$receiver_matches = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get match history
$query = "SELECT m.*, p.amount,
          CASE WHEN m.sender_id = :user_id THEN u.name ELSE us.name END as other_party_name,
          CASE WHEN m.sender_id = :user_id THEN 'sender' ELSE 'receiver' END as role
          FROM matches m
          JOIN pledges p ON m.pledge_id = p.id
          JOIN users u ON m.receiver_id = u.id
          JOIN users us ON m.sender_id = us.id
          WHERE (m.sender_id = :user_id OR m.receiver_id = :user_id) AND m.status IN ('completed', 'disputed', 'cancelled')
          ORDER BY m.updated_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$match_history = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Matches</h1>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <!-- Matches as Sender -->
    <?php if (!empty($sender_matches)): ?>
        <?php foreach ($sender_matches as $match): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Current Match (You are the Sender)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Receiver Details</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-mobile-responsive">
                                    <tr>
                                        <th data-label="Name">Name</th>
                                        <td data-label="Name"><?php echo $match->receiver_name; ?></td>
                                    </tr>
                                    <tr>
                                        <th data-label="Mobile Number">Mobile Number</th>
                                        <td data-label="Mobile Number">
                                            <div class="d-flex align-items-center">
                                                <span class="mr-2"><?php echo $match->mobile_number; ?></span>
                                                <button class="btn btn-sm btn-outline-secondary copy-btn" data-clipboard-text="<?php echo $match->mobile_number; ?>">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th data-label="Mobile Name">Mobile Name</th>
                                        <td data-label="Mobile Name"><?php echo $match->mobile_name; ?></td>
                                    </tr>
                                    <tr>
                                        <th data-label="Amount">Amount</th>
                                        <td data-label="Amount"><?php echo format_currency($match->amount, 'Tokens'); ?></td>
                                    </tr>
                                    <tr>
                                        <th data-label="Deadline">Deadline</th>
                                        <td data-label="Deadline">
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
                                </tr>
                            </table>

                            <div class="alert alert-info">
                                <p class="mb-0">Please send the payment to the receiver using the mobile number provided above, then upload proof of payment.</p>
                            </div>

                            <a href="chat.php?match_id=<?php echo $match->id; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-comments"></i> Chat with Receiver
                            </a>
                        </div>

                        <div class="col-md-6">
                            <?php if ($match->status == 'pending'): ?>
                                <h6>Upload Payment Proof</h6>
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="match_id" value="<?php echo $match->id; ?>">

                                    <div class="form-group">
                                        <label for="transaction_id">Transaction ID / Reference</label>
                                        <input type="text" name="transaction_id" class="form-control <?php echo (!empty($transaction_id_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $transaction_id; ?>">
                                        <span class="invalid-feedback"><?php echo $transaction_id_err; ?></span>
                                    </div>

                                    <div class="form-group">
                                        <label for="proof_file">Upload Proof of Payment</label>
                                        <div class="custom-file">
                                            <input type="file" name="proof_file" class="custom-file-input <?php echo (!empty($file_err)) ? 'is-invalid' : ''; ?>" id="proof_file" accept=".jpg,.jpeg,.png,.pdf">
                                            <label class="custom-file-label" for="proof_file">Choose file</label>
                                            <span class="invalid-feedback"><?php echo $file_err; ?></span>
                                        </div>
                                        <small class="form-text text-muted">Accepted formats: JPG, PNG, PDF. Max size: 5MB</small>
                                    </div>

                                    <div class="form-group">
                                        <img id="image-preview" class="img-fluid mb-3" style="display: none; max-height: 200px;">
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" name="upload_proof" class="btn btn-primary">Submit Proof</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <h6>Payment Sent</h6>
                                <div class="alert alert-success">
                                    <p>You have sent the payment. Waiting for the receiver to confirm receipt.</p>
                                    <p><strong>Transaction ID:</strong> <?php echo $match->transaction_id; ?></p>
                                    <?php if (!empty($match->proof_file)): ?>
                                        <p><strong>Proof:</strong> <a href="uploads/proofs/<?php echo $match->proof_file; ?>" target="_blank">View Proof</a></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Matches as Receiver -->
    <?php if (!empty($receiver_matches)): ?>
        <?php foreach ($receiver_matches as $match): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Current Match (You are the Receiver)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Match Details</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-mobile-responsive">
                                    <tr>
                                        <th data-label="Sender">Sender</th>
                                        <td data-label="Sender"><?php echo $match->sender_name; ?></td>
                                    </tr>
                                    <tr>
                                        <th data-label="Amount">Amount</th>
                                        <td data-label="Amount"><?php echo format_currency($match->amount, 'Tokens'); ?></td>
                                    </tr>
                                    <tr>
                                        <th data-label="Status">Status</th>
                                        <td data-label="Status">
                                            <?php
                                            switch ($match->status) {
                                                case 'pending':
                                                    echo '<span class="badge badge-warning">Waiting for Payment</span>';
                                                    break;
                                                case 'payment_sent':
                                                    echo '<span class="badge badge-info">Payment Sent</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge badge-secondary">Unknown</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th data-label="Deadline">Deadline</th>
                                        <td data-label="Deadline">
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
                                </tr>
                            </table>

                            <a href="chat.php?match_id=<?php echo $match->id; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-comments"></i> Chat with Sender
                            </a>
                        </div>

                        <div class="col-md-6">
                            <?php if ($match->status == 'pending'): ?>
                                <div class="alert alert-info">
                                    <h6>Waiting for Payment</h6>
                                    <p>The sender has been matched with you and should send the payment soon.</p>
                                    <p>You will be notified when the payment is sent.</p>
                                </div>
                            <?php else: ?>
                                <h6>Payment Received</h6>
                                <div class="alert alert-success">
                                    <p>The sender has sent the payment. Please check if you have received it.</p>
                                    <p><strong>Transaction ID:</strong> <?php echo $match->transaction_id; ?></p>
                                    <?php if (!empty($match->proof_file)): ?>
                                        <p><strong>Proof:</strong> <a href="uploads/proofs/<?php echo $match->proof_file; ?>" target="_blank">View Proof</a></p>
                                    <?php endif; ?>
                                </div>

                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <input type="hidden" name="match_id" value="<?php echo $match->id; ?>">
                                    <div class="form-group">
                                        <button type="submit" name="confirm_receipt" class="btn btn-success" onclick="return confirm('Are you sure you have received the payment?')">
                                            <i class="fas fa-check"></i> Confirm Receipt
                                        </button>
                                        <a href="dispute.php?match_id=<?php echo $match->id; ?>" class="btn btn-danger">
                                            <i class="fas fa-exclamation-triangle"></i> Report Problem
                                        </a>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (empty($sender_matches) && empty($receiver_matches)): ?>
        <div class="alert alert-info">
            <p class="mb-0">You don't have any active matches at the moment.</p>
        </div>
    <?php endif; ?>

    <!-- Match History -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Match History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-mobile-responsive">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Role</th>
                            <th>Other Party</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($match_history)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No match history found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($match_history as $match): ?>
                                <tr>
                                    <td data-label="Date"><?php echo format_date($match->created_at); ?></td>
                                    <td data-label="Amount"><?php echo format_currency($match->amount, 'Tokens'); ?></td>
                                    <td data-label="Role">
                                        <?php echo ucfirst($match->role); ?>
                                    </td>
                                    <td data-label="Other Party"><?php echo $match->other_party_name; ?></td>
                                    <td data-label="Status">
                                        <?php
                                        switch ($match->status) {
                                            case 'completed':
                                                echo '<span class="badge badge-success">Completed</span>';
                                                break;
                                            case 'disputed':
                                                echo '<span class="badge badge-danger">Disputed</span>';
                                                break;
                                            case 'cancelled':
                                                echo '<span class="badge badge-secondary">Cancelled</span>';
                                                break;
                                            default:
                                                echo '<span class="badge badge-secondary">Unknown</span>';
                                        }
                                        ?>
                                    </td>
                                    <td data-label="Actions">
                                        <div class="btn-group">
                                            <a href="chat.php?match_id=<?php echo $match->id; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-comments"></i> <span class="d-none d-md-inline">Chat</span>
                                            </a>
                                            <?php if (!empty($match->proof_file)): ?>
                                                <a href="uploads/proofs/<?php echo $match->proof_file; ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-file-alt"></i> <span class="d-none d-md-inline">Proof</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
