<?php
// Set page title
$page_title = 'Dispute Management';

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

// Process dispute resolution
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resolve_dispute'])) {
    $dispute_id = isset($_POST['dispute_id']) ? (int)$_POST['dispute_id'] : 0;
    $resolution = isset($_POST['resolution']) ? trim($_POST['resolution']) : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    // Debug message
    echo "<script>console.log('Processing dispute resolution for ID: " . $dispute_id . ", Status: " . $status . "');</script>";

    if (!empty($dispute_id) && !empty($resolution) && !empty($status)) {
        // Start transaction
        $db->beginTransaction();

        try {
            // Update dispute
            $query = "UPDATE disputes SET
                     status = :status,
                     resolution = :resolution,
                     resolved_at = NOW(),
                     resolved_by = :admin_id
                     WHERE id = :dispute_id";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':resolution', $resolution);
            $stmt->bindParam(':admin_id', $_SESSION['user_id']);
            $stmt->bindParam(':dispute_id', $dispute_id);

            if (!$stmt->execute()) {
                $error_info = $stmt->errorInfo();
                throw new Exception("SQL Error: " . $error_info[2]);
            }

            // Get dispute details
            $query = "SELECT d.*, m.sender_id, m.receiver_id, m.status as match_status
                     FROM disputes d
                     JOIN matches m ON d.match_id = m.id
                     WHERE d.id = :dispute_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':dispute_id', $dispute_id);

            if (!$stmt->execute()) {
                $error_info = $stmt->errorInfo();
                throw new Exception("SQL Error (get dispute): " . $error_info[2]);
            }

            $dispute = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$dispute) {
                throw new Exception("Dispute not found with ID: " . $dispute_id);
            }

            // Update match status based on resolution
            if ($status === 'resolved_sender') {
                $match_status = 'payment_sent';
            } elseif ($status === 'resolved_receiver') {
                $match_status = 'completed';
            } elseif ($status === 'resolved') {
                $match_status = 'completed'; // Default to completed for generic resolution
            } else {
                $match_status = 'cancelled';
            }

            $query = "UPDATE matches SET status = :status WHERE id = :match_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':status', $match_status);
            $stmt->bindParam(':match_id', $dispute->match_id);

            if (!$stmt->execute()) {
                $error_info = $stmt->errorInfo();
                throw new Exception("SQL Error (update match): " . $error_info[2]);
            }

            // Create notifications for both parties
            $resolution_message = "Your dispute for match #{$dispute->match_id} has been resolved. Resolution: {$resolution}";

            create_notification($dispute->sender_id, 'Dispute Resolved', $resolution_message, 'dispute', $db);
            create_notification($dispute->receiver_id, 'Dispute Resolved', $resolution_message, 'dispute', $db);

            // Commit transaction
            $db->commit();

            $_SESSION['success_message'] = 'Dispute has been resolved successfully.';
            redirect('disputes.php');
        } catch (Exception $e) {
            // Rollback transaction
            $db->rollBack();
            $_SESSION['error_message'] = 'Error resolving dispute: ' . $e->getMessage();

            // Debug message
            echo "<script>console.error('SQL Error: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}

// Get disputes with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "SELECT d.*,
          m.status as match_status,
          m.sender_id,
          m.receiver_id,
          us.name as sender_name,
          ur.name as receiver_name,
          p.amount
          FROM disputes d
          JOIN matches m ON d.match_id = m.id
          JOIN users us ON m.sender_id = us.id
          JOIN users ur ON m.receiver_id = ur.id
          JOIN pledges p ON m.pledge_id = p.id
          WHERE 1=1";

if (!empty($status_filter)) {
    $query .= " AND d.status = :status";
}
if (!empty($search)) {
    $query .= " AND (us.name LIKE :search OR ur.name LIKE :search OR d.match_id LIKE :search)";
}

$query .= " ORDER BY d.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
if (!empty($status_filter)) {
    $stmt->bindParam(':status', $status_filter);
}
if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bindParam(':search', $search_param);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$disputes = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get total disputes count for pagination
$count_query = "SELECT COUNT(*) as total FROM disputes d
                JOIN matches m ON d.match_id = m.id
                JOIN users us ON m.sender_id = us.id
                JOIN users ur ON m.receiver_id = ur.id
                WHERE 1=1";

if (!empty($status_filter)) {
    $count_query .= " AND d.status = :status";
}
if (!empty($search)) {
    $count_query .= " AND (us.name LIKE :search OR ur.name LIKE :search OR d.match_id LIKE :search)";
}

$count_stmt = $db->prepare($count_query);
if (!empty($status_filter)) {
    $count_stmt->bindParam(':status', $status_filter);
}
if (!empty($search)) {
    $count_stmt->bindParam(':search', $search_param);
}
$count_stmt->execute();
$result = $count_stmt->fetch(PDO::FETCH_OBJ);
$total_disputes = $result->total;
$total_pages = ceil($total_disputes / $limit);
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

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dispute Management</h1>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
                <?php endif; ?>

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Search disputes..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>Open</option>
                                    <option value="resolved_sender" <?php echo $status_filter === 'resolved_sender' ? 'selected' : ''; ?>>Resolved (Sender)</option>
                                    <option value="resolved_receiver" <?php echo $status_filter === 'resolved_receiver' ? 'selected' : ''; ?>>Resolved (Receiver)</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="disputes.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Disputes Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Match ID</th>
                                        <th>Amount</th>
                                        <th>Sender</th>
                                        <th>Receiver</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($disputes as $dispute): ?>
                                    <tr>
                                        <td><?php echo $dispute->id; ?></td>
                                        <td>#<?php echo $dispute->match_id; ?></td>
                                        <td><?php echo number_format($dispute->amount, 2); ?> Tokens</td>
                                        <td><?php echo htmlspecialchars($dispute->sender_name); ?></td>
                                        <td><?php echo htmlspecialchars($dispute->receiver_name); ?></td>
                                        <td>
                                            <span class="badge badge-<?php
                                                echo $dispute->status === 'open' ? 'warning' :
                                                    ($dispute->status === 'cancelled' ? 'danger' : 'success');
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $dispute->status)); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($dispute->created_at)); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary view-dispute"
                                                    data-id="<?php echo $dispute->id; ?>"
                                                    data-match="<?php echo $dispute->match_id; ?>"
                                                    data-reason="<?php echo htmlspecialchars($dispute->reason); ?>"
                                                    data-evidence="<?php echo $dispute->evidence_file; ?>"
                                                    data-status="<?php echo $dispute->status; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Dispute Modal -->
    <div class="modal fade" id="viewDisputeModal" tabindex="-1" role="dialog" aria-labelledby="viewDisputeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewDisputeModalLabel">Dispute Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Match ID:</label>
                        <div id="dispute-match" class="font-weight-bold"></div>
                    </div>
                    <div class="form-group">
                        <label>Reason:</label>
                        <div id="dispute-reason" class="p-2 bg-light rounded"></div>
                    </div>
                    <div class="form-group">
                        <label>Evidence:</label>
                        <div id="dispute-evidence"></div>
                    </div>
                    <form id="resolveDisputeForm" method="POST">
                        <input type="hidden" name="dispute_id" id="dispute-id">
                        <input type="hidden" name="resolve_dispute" value="1">

                        <div class="form-group">
                            <label for="status">Resolution Status:</label>
                            <select name="status" class="form-control" required>
                                <option value="">Select Resolution</option>
                                <option value="resolved_sender">Resolve in Favor of Sender</option>
                                <option value="resolved_receiver">Resolve in Favor of Receiver</option>
                                <option value="cancelled">Cancel Match</option>
                                <!-- For debugging -->
                                <option value="resolved">Mark as Resolved (Generic)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="resolution">Resolution Details:</label>
                            <textarea name="resolution" class="form-control" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Resolve Dispute</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <?php include 'includes/dark_mode_script.php'; ?>
    <script>
    $(document).ready(function() {
        $('.view-dispute').click(function() {
            var id = $(this).data('id');
            var match = $(this).data('match');
            var reason = $(this).data('reason');
            var evidence = $(this).data('evidence');
            var status = $(this).data('status');

            console.log('Opening dispute:', id, 'Status:', status);

            $('#dispute-id').val(id);
            $('#dispute-match').text('#' + match);
            $('#dispute-reason').text(reason);

            if (evidence) {
                $('#dispute-evidence').html('<a href="../uploads/evidence/' + evidence + '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-file-alt"></i> View Evidence</a>');
            } else {
                $('#dispute-evidence').html('<em>No evidence file uploaded</em>');
            }

            if (status !== 'open') {
                console.log('Dispute is not open, hiding form');
                $('#resolveDisputeForm').hide();
            } else {
                console.log('Dispute is open, showing form');
                $('#resolveDisputeForm').show();
            }

            $('#viewDisputeModal').modal('show');
        });

        // Add form submission handler
        $('#resolveDisputeForm').on('submit', function(e) {
            var disputeId = $('#dispute-id').val();
            var status = $(this).find('select[name="status"]').val();
            var resolution = $(this).find('textarea[name="resolution"]').val();

            console.log('Submitting resolution for dispute:', disputeId);
            console.log('Status:', status);
            console.log('Resolution:', resolution);

            if (!status || !resolution) {
                e.preventDefault();
                alert('Please select a resolution status and provide resolution details.');
                return false;
            }
        });
    });
    </script>
</body>
</html>