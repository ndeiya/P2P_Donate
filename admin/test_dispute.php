<?php
// Set page title
$page_title = 'Test Dispute Resolution';

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

// Get dispute ID from query string
$dispute_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no dispute ID is provided, find an open dispute
if ($dispute_id === 0) {
    $query = "SELECT d.*, m.sender_id, m.receiver_id, m.status as match_status
              FROM disputes d
              JOIN matches m ON d.match_id = m.id
              WHERE d.status = 'open'
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $dispute = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($dispute) {
        $dispute_id = $dispute->id;
    }
}

// If still no dispute ID, create a test dispute
if ($dispute_id === 0) {
    // Find a match to create a dispute for
    $query = "SELECT * FROM matches WHERE status = 'pending' OR status = 'payment_sent' LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $match = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($match) {
        // Create a test dispute
        $query = "INSERT INTO disputes (match_id, user_id, reason, status)
                  VALUES (:match_id, :user_id, :reason, 'open')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':match_id', $match->id);
        $stmt->bindParam(':user_id', $match->sender_id);
        $reason = "Test dispute for debugging purposes";
        $stmt->bindParam(':reason', $reason);
        
        if ($stmt->execute()) {
            $dispute_id = $db->lastInsertId();
            
            // Update match status
            $query = "UPDATE matches SET status = 'disputed' WHERE id = :match_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':match_id', $match->id);
            $stmt->execute();
            
            $success_message = "Created test dispute with ID: " . $dispute_id;
        } else {
            $error_message = "Failed to create test dispute: " . print_r($stmt->errorInfo(), true);
        }
    } else {
        $error_message = "No suitable matches found to create a dispute.";
    }
}

// Get dispute details
if ($dispute_id > 0) {
    $query = "SELECT d.*, m.id as match_id, m.sender_id, m.receiver_id, m.status as match_status,
              sender.name as sender_name, receiver.name as receiver_name
              FROM disputes d
              JOIN matches m ON d.match_id = m.id
              JOIN users sender ON m.sender_id = sender.id
              JOIN users receiver ON m.receiver_id = receiver.id
              WHERE d.id = :dispute_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':dispute_id', $dispute_id);
    $stmt->execute();
    $dispute = $stmt->fetch(PDO::FETCH_OBJ);
    
    if (!$dispute) {
        $error_message = "Dispute not found with ID: " . $dispute_id;
    }
}

// Process dispute resolution
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resolve_dispute'])) {
    $resolution = isset($_POST['resolution']) ? trim($_POST['resolution']) : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    
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
            $query = "SELECT * FROM disputes WHERE id = :dispute_id";
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
            
            // Create notifications for both users
            create_notification($dispute->sender_id, 'Dispute Resolved', 'Your dispute has been resolved. Resolution: ' . $resolution, 'dispute', $db);
            create_notification($dispute->receiver_id, 'Dispute Resolved', 'Your dispute has been resolved. Resolution: ' . $resolution, 'dispute', $db);
            
            // Commit transaction
            $db->commit();
            
            $success_message = 'Dispute has been resolved successfully.';
            
            // Refresh dispute details
            $query = "SELECT d.*, m.id as match_id, m.sender_id, m.receiver_id, m.status as match_status,
                      sender.name as sender_name, receiver.name as receiver_name
                      FROM disputes d
                      JOIN matches m ON d.match_id = m.id
                      JOIN users sender ON m.sender_id = sender.id
                      JOIN users receiver ON m.receiver_id = receiver.id
                      WHERE d.id = :dispute_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':dispute_id', $dispute_id);
            $stmt->execute();
            $dispute = $stmt->fetch(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            // Rollback transaction
            $db->rollBack();
            $error_message = 'Error resolving dispute: ' . $e->getMessage();
        }
    } else {
        $error_message = 'Please fill in all required fields.';
    }
}
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
                    <h1 class="h2">Test Dispute Resolution</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="disputes.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Disputes
                        </a>
                    </div>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <?php if (isset($dispute) && $dispute): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Dispute #<?php echo $dispute->id; ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>Match ID</th>
                                            <td>#<?php echo $dispute->match_id; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Sender</th>
                                            <td><?php echo htmlspecialchars($dispute->sender_name); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Receiver</th>
                                            <td><?php echo htmlspecialchars($dispute->receiver_name); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Reason</th>
                                            <td><?php echo htmlspecialchars($dispute->reason); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>Dispute Status</th>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $dispute->status === 'open' ? 'warning' : 
                                                        ($dispute->status === 'resolved' || $dispute->status === 'resolved_sender' || $dispute->status === 'resolved_receiver' ? 'success' : 
                                                            ($dispute->status === 'cancelled' ? 'danger' : 'info')); 
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $dispute->status)); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Match Status</th>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $dispute->match_status === 'completed' ? 'success' : 
                                                        ($dispute->match_status === 'disputed' ? 'warning' : 
                                                            ($dispute->match_status === 'cancelled' ? 'danger' : 'info')); 
                                                ?>">
                                                    <?php echo ucfirst($dispute->match_status); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Created</th>
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($dispute->created_at)); ?></td>
                                        </tr>
                                        <?php if ($dispute->status !== 'open'): ?>
                                        <tr>
                                            <th>Resolution</th>
                                            <td><?php echo htmlspecialchars($dispute->resolution); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Resolved At</th>
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($dispute->resolved_at)); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>

                            <?php if ($dispute->status === 'open'): ?>
                                <div class="mt-4">
                                    <h5>Resolve Dispute</h5>
                                    <form method="POST">
                                        <input type="hidden" name="resolve_dispute" value="1">
                                        <div class="form-group">
                                            <label for="status">Resolution Status:</label>
                                            <select name="status" class="form-control" required>
                                                <option value="">Select Resolution</option>
                                                <option value="resolved_sender">Resolve in Favor of Sender</option>
                                                <option value="resolved_receiver">Resolve in Favor of Receiver</option>
                                                <option value="cancelled">Cancel Match</option>
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
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No dispute found or created.</div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <?php include 'includes/dark_mode_script.php'; ?>
</body>
</html>
