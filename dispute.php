<?php
// Set page title
$page_title = 'Dispute Resolution';

// Include header
require_once 'includes/header.php';

// Initialize variables
$match_id = isset($_GET['match_id']) ? $_GET['match_id'] : '';
$reason = '';
$match_id_err = $reason_err = $file_err = '';
$success_message = '';
$error_message = '';

// Get user's matches for dropdown
$query = "SELECT m.id,
          CASE WHEN m.sender_id = :user_id THEN 'Sender' ELSE 'Receiver' END as role,
          CASE WHEN m.sender_id = :user_id THEN u.name ELSE us.name END as other_party_name,
          p.amount
          FROM matches m
          JOIN pledges p ON m.pledge_id = p.id
          JOIN users u ON m.receiver_id = u.id
          JOIN users us ON m.sender_id = us.id
          WHERE (m.sender_id = :user_id OR m.receiver_id = :user_id)
          AND m.status IN ('pending', 'payment_sent')
          ORDER BY m.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user_matches = $stmt->fetchAll(PDO::FETCH_OBJ);

// Process dispute form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_dispute'])) {
    // Validate match ID
    if (empty($_POST['match_id'])) {
        $match_id_err = 'Please select a match.';
    } else {
        $match_id = $_POST['match_id'];

        // Check if match exists and belongs to user
        $query = "SELECT * FROM matches WHERE id = :id AND (sender_id = :user_id OR receiver_id = :user_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $match_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            $match_id_err = 'Invalid match selected.';
        }

        // Check if dispute already exists for this match
        $query = "SELECT * FROM disputes WHERE match_id = :match_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':match_id', $match_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $match_id_err = 'A dispute already exists for this match.';
        }
    }

    // Validate reason
    if (empty(trim($_POST['reason']))) {
        $reason_err = 'Please enter a reason for the dispute.';
    } else {
        $reason = sanitize($_POST['reason']);
    }

    // Process file upload if provided
    $evidence_file = null;
    if (isset($_FILES['evidence_file']) && $_FILES['evidence_file']['error'] != UPLOAD_ERR_NO_FILE) {
        $upload_result = upload_file($_FILES['evidence_file'], 'uploads/evidence/');

        if (!$upload_result['success']) {
            $file_err = $upload_result['message'];
        } else {
            $evidence_file = $upload_result['filename'];
        }
    }

    // If no errors, create the dispute
    if (empty($match_id_err) && empty($reason_err) && empty($file_err)) {
        // Get match details
        $query = "SELECT * FROM matches WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $match_id);
        $stmt->execute();
        $match = $stmt->fetch(PDO::FETCH_OBJ);

        // Start transaction
        $db->beginTransaction();

        try {
            // Insert dispute record
            $query = "INSERT INTO disputes (match_id, user_id, reason, evidence_file) VALUES (:match_id, :user_id, :reason, :evidence_file)";
            $stmt = $db->prepare($query);

            $stmt->bindParam(':match_id', $match_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':reason', $reason);
            $stmt->bindParam(':evidence_file', $evidence_file);

            $stmt->execute();

            // Update match status
            $query = "UPDATE matches SET status = 'disputed' WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $match_id);
            $stmt->execute();

            // Create notification for the user
            create_notification($user_id, 'Dispute Created', 'Your dispute for match #' . $match_id . ' has been submitted and is under review.', 'dispute', $db);

            // Create notification for the other party
            $other_user_id = ($match->sender_id == $user_id) ? $match->receiver_id : $match->sender_id;
            create_notification($other_user_id, 'Dispute Filed', 'A dispute has been filed for match #' . $match_id . '. Please check your email for further instructions.', 'dispute', $db);

            // Create notification for admin
            $query = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_OBJ);

            if ($admin) {
                create_notification($admin->id, 'New Dispute', 'A new dispute has been filed for match #' . $match_id . '.', 'dispute', $db);
            }

            // Commit transaction
            $db->commit();

            // Set success message
            $success_message = 'Your dispute has been submitted successfully. An administrator will review it shortly.';

            // Clear form data
            $match_id = $reason = '';
        } catch (Exception $e) {
            // Rollback transaction
            $db->rollBack();

            // Set error message
            $error_message = 'Something went wrong. Please try again.';
        }
    }
}

// Get user's disputes
$query = "SELECT d.*, m.status as match_status,
          CASE WHEN m.sender_id = :user_id THEN 'Sender' ELSE 'Receiver' END as role,
          CASE WHEN m.sender_id = :user_id THEN u.name ELSE us.name END as other_party_name
          FROM disputes d
          JOIN matches m ON d.match_id = m.id
          JOIN users u ON m.receiver_id = u.id
          JOIN users us ON m.sender_id = us.id
          WHERE d.user_id = :user_id
          ORDER BY d.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user_disputes = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dispute Resolution</h1>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <!-- Report a Problem -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Report a Problem</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($user_matches)): ?>
                        <div class="alert alert-info">
                            <p class="mb-0">You don't have any active matches to report a problem for.</p>
                        </div>
                    <?php else: ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="match_id">Select Match</label>
                                <select name="match_id" class="form-control <?php echo (!empty($match_id_err)) ? 'is-invalid' : ''; ?>">
                                    <option value="">-- Select Match --</option>
                                    <?php foreach ($user_matches as $match): ?>
                                        <option value="<?php echo $match->id; ?>" <?php echo ($match_id == $match->id) ? 'selected' : ''; ?>>
                                            Match #<?php echo $match->id; ?> - <?php echo $match->amount; ?> Tokens (<?php echo $match->role; ?> - <?php echo $match->other_party_name; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="invalid-feedback"><?php echo $match_id_err; ?></span>
                            </div>

                            <div class="form-group">
                                <label for="reason">Reason for Dispute</label>
                                <textarea name="reason" class="form-control <?php echo (!empty($reason_err)) ? 'is-invalid' : ''; ?>" rows="5"><?php echo $reason; ?></textarea>
                                <span class="invalid-feedback"><?php echo $reason_err; ?></span>
                                <small class="form-text text-muted">Please provide a detailed explanation of the issue.</small>
                            </div>

                            <div class="form-group">
                                <label for="evidence_file">Upload Evidence (Optional)</label>
                                <div class="custom-file">
                                    <input type="file" name="evidence_file" class="custom-file-input <?php echo (!empty($file_err)) ? 'is-invalid' : ''; ?>" id="evidence_file">
                                    <label class="custom-file-label" for="evidence_file">Choose file</label>
                                    <span class="invalid-feedback"><?php echo $file_err; ?></span>
                                </div>
                                <small class="form-text text-muted">Accepted formats: JPG, PNG, PDF. Max size: 5MB</small>
                            </div>

                            <div class="form-group">
                                <button type="submit" name="submit_dispute" class="btn btn-primary">Submit Dispute</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Your Disputes -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Your Disputes</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($user_disputes)): ?>
                        <div class="alert alert-info">
                            <p class="mb-0">You haven't filed any disputes yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Match</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_disputes as $dispute): ?>
                                        <tr>
                                            <td><?php echo format_date($dispute->created_at); ?></td>
                                            <td>Match #<?php echo $dispute->match_id; ?> (<?php echo $dispute->role; ?>)</td>
                                            <td>
                                                <?php
                                                switch ($dispute->status) {
                                                    case 'open':
                                                        echo '<span class="badge badge-warning">Open</span>';
                                                        break;
                                                    case 'under_review':
                                                        echo '<span class="badge badge-info">Under Review</span>';
                                                        break;
                                                    case 'resolved':
                                                        echo '<span class="badge badge-success">Resolved</span>';
                                                        break;
                                                    case 'resolved_sender':
                                                        echo '<span class="badge badge-success">Resolved (Sender)</span>';
                                                        break;
                                                    case 'resolved_receiver':
                                                        echo '<span class="badge badge-success">Resolved (Receiver)</span>';
                                                        break;
                                                    case 'cancelled':
                                                        echo '<span class="badge badge-danger">Cancelled</span>';
                                                        break;
                                                    case 'closed':
                                                        echo '<span class="badge badge-secondary">Closed</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge badge-secondary">Unknown (' . htmlspecialchars($dispute->status) . ')</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary view-dispute" data-id="<?php echo $dispute->id; ?>" data-reason="<?php echo htmlspecialchars($dispute->reason); ?>" data-status="<?php echo $dispute->status; ?>" data-resolution="<?php echo htmlspecialchars($dispute->resolution); ?>" data-evidence="<?php echo $dispute->evidence_file; ?>">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <!-- Guidelines for Disputes -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Guidelines for Disputes</h5>
                </div>
                <div class="card-body">
                    <h6>What qualifies as a valid dispute?</h6>
                    <ul>
                        <li>Sender claims to have sent payment but receiver hasn't received it</li>
                        <li>Receiver claims payment was not received despite sender's proof</li>
                        <li>Payment amount doesn't match the agreed amount</li>
                        <li>Technical issues with the payment platform</li>
                    </ul>

                    <h6>Dispute Resolution Process</h6>
                    <ol>
                        <li>Submit a dispute with all relevant details and evidence</li>
                        <li>Both parties will be notified of the dispute</li>
                        <li>An administrator will review the case within 24-48 hours</li>
                        <li>Additional information may be requested from either party</li>
                        <li>A decision will be made based on the evidence provided</li>
                        <li>Both parties will be notified of the resolution</li>
                    </ol>

                    <h6>Tips for a Smooth Resolution</h6>
                    <ul>
                        <li>Always keep proof of payment (screenshots, transaction IDs)</li>
                        <li>Communicate clearly with the other party through the chat system</li>
                        <li>Respond promptly to any requests for additional information</li>
                        <li>Be honest and accurate in your statements</li>
                    </ul>

                    <div class="alert alert-info mt-3">
                        <p class="mb-0">For urgent assistance, please contact support at support@p2pdonate.com</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Dispute Modal -->
<div class="modal fade" id="view-dispute-modal" tabindex="-1" role="dialog" aria-labelledby="viewDisputeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewDisputeModalLabel">Dispute Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Status</label>
                    <div id="dispute-status"></div>
                </div>
                <div class="form-group">
                    <label>Reason</label>
                    <div id="dispute-reason" class="p-2 bg-light rounded"></div>
                </div>
                <div id="evidence-container" class="form-group">
                    <label>Evidence</label>
                    <div id="dispute-evidence"></div>
                </div>
                <div id="resolution-container" class="form-group">
                    <label>Resolution</label>
                    <div id="dispute-resolution" class="p-2 bg-light rounded"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // View dispute details
    $('.view-dispute').on('click', function() {
        try {
            var id = $(this).data('id');
            var reason = $(this).data('reason');
            var status = $(this).data('status');
            var resolution = $(this).data('resolution');
            var evidence = $(this).data('evidence');

            console.log('Viewing dispute:', id);
            console.log('Status:', status);
            console.log('Reason:', reason);
            console.log('Resolution:', resolution);
            console.log('Evidence:', evidence);

            // Check if modal exists
            if ($('#view-dispute-modal').length === 0) {
                console.error('Modal not found: #view-dispute-modal');
                alert('Error: Modal not found. Please contact support.');
                return;
            }

        // Set modal content
        $('#dispute-reason').text(reason);

        // Set status badge
        var statusBadge = '';
        switch (status) {
            case 'open':
                statusBadge = '<span class="badge badge-warning">Open</span>';
                break;
            case 'under_review':
                statusBadge = '<span class="badge badge-info">Under Review</span>';
                break;
            case 'resolved':
                statusBadge = '<span class="badge badge-success">Resolved</span>';
                break;
            case 'resolved_sender':
                statusBadge = '<span class="badge badge-success">Resolved (Sender)</span>';
                break;
            case 'resolved_receiver':
                statusBadge = '<span class="badge badge-success">Resolved (Receiver)</span>';
                break;
            case 'cancelled':
                statusBadge = '<span class="badge badge-danger">Cancelled</span>';
                break;
            case 'closed':
                statusBadge = '<span class="badge badge-secondary">Closed</span>';
                break;
            default:
                statusBadge = '<span class="badge badge-secondary">Unknown (' + status + ')</span>';
                console.log('Unknown dispute status:', status);
        }
        $('#dispute-status').html(statusBadge);

        // Show/hide evidence
        if (evidence) {
            $('#evidence-container').show();
            $('#dispute-evidence').html('<a href="uploads/evidence/' + evidence + '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-file-alt"></i> View Evidence</a>');
        } else {
            $('#evidence-container').hide();
        }

        // Show/hide resolution
        if (resolution) {
            $('#resolution-container').show();
            $('#dispute-resolution').text(resolution);
        } else {
            $('#resolution-container').hide();
        }

        // Show modal
        $('#view-dispute-modal').modal('show');
        console.log('Modal should be visible now');
        } catch (error) {
            console.error('Error showing dispute modal:', error);
            alert('An error occurred while trying to view the dispute details. Please try again or contact support.');
        }
    });

    // Custom file input label
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>
