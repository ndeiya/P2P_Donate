<?php
// Set page title
$page_title = 'Admin Dashboard';

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

// Get statistics
// Total users
$query = "SELECT COUNT(*) as count FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);
$total_users = $result->count;

// Active users (with at least one pledge or match)
$query = "SELECT COUNT(DISTINCT user_id) as count FROM pledges";
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);
$active_users = $result->count;

// Total tokens
$query = "SELECT SUM(token_balance) as total FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);
$total_tokens = $result->total;

// Pending pledges
$query = "SELECT COUNT(*) as count FROM pledges WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);
$pending_pledges = $result->count;

// Active matches
$query = "SELECT COUNT(*) as count FROM matches WHERE status IN ('pending', 'payment_sent')";
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);
$active_matches = $result->count;

// Open disputes
$query = "SELECT COUNT(*) as count FROM disputes WHERE status IN ('open', 'under_review')";
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);
$open_disputes = $result->count;

// Recent activities
$query = "SELECT 'pledge' as type, p.id, p.amount, p.status, p.created_at, u.name as user_name
          FROM pledges p
          JOIN users u ON p.user_id = u.id
          UNION
          SELECT 'match' as type, m.id, m.amount, m.status, m.created_at, u.name as user_name
          FROM matches m
          JOIN users u ON m.sender_id = u.id
          UNION
          SELECT 'dispute' as type, d.id, 0 as amount, d.status, d.created_at, u.name as user_name
          FROM disputes d
          JOIN users u ON d.user_id = u.id
          ORDER BY created_at DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_activities = $stmt->fetchAll(PDO::FETCH_OBJ);

// Pending token purchases
$query = "SELECT t.*, u.name as user_name
          FROM tokens t
          JOIN users u ON t.user_id = u.id
          WHERE t.transaction_type = 'purchase' AND t.status = 'pending'
          ORDER BY t.created_at DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$pending_purchases = $stmt->fetchAll(PDO::FETCH_OBJ);
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
                    <h1 class="h2">Admin Dashboard</h1>
                </div>

                <!-- Overview Stats -->
                <div class="row">
                    <div class="col-md-4 col-xl-3">
                        <div class="card bg-primary text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Total Users</h6>
                                        <h2 class="mb-0"><?php echo $total_users; ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="users.php">View Details</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-xl-3">
                        <div class="card bg-success text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Total Tokens</h6>
                                        <h2 class="mb-0"><?php echo number_format($total_tokens, 2); ?></h2>
                                    </div>
                                    <i class="fas fa-coins fa-2x"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="tokens.php">View Details</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-xl-3">
                        <div class="card bg-warning text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Pending Pledges</h6>
                                        <h2 class="mb-0"><?php echo $pending_pledges; ?></h2>
                                    </div>
                                    <i class="fas fa-hand-holding-usd fa-2x"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="pledges.php">View Details</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-xl-3">
                        <div class="card bg-danger text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Open Disputes</h6>
                                        <h2 class="mb-0"><?php echo $open_disputes; ?></h2>
                                    </div>
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="disputes.php">View Details</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Pending Token Purchases -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Pending Token Purchases</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($pending_purchases)): ?>
                                    <div class="alert alert-info">
                                        <p class="mb-0">No pending token purchases.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Amount</th>
                                                    <th>Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pending_purchases as $purchase): ?>
                                                    <tr>
                                                        <td><?php echo $purchase->user_name; ?></td>
                                                        <td><?php echo format_currency($purchase->amount, 'Tokens'); ?></td>
                                                        <td><?php echo format_date($purchase->created_at); ?></td>
                                                        <td>
                                                            <a href="tokens.php?action=view&id=<?php echo $purchase->id; ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-eye"></i> View
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-right">
                                        <a href="tokens.php" class="btn btn-outline-primary btn-sm">View All</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <a href="tokens.php?action=credit" class="btn btn-primary btn-block">
                                            <i class="fas fa-coins"></i> Credit Tokens
                                        </a>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <a href="pledges.php?action=match" class="btn btn-success btn-block">
                                            <i class="fas fa-exchange-alt"></i> Match Pledges
                                        </a>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <a href="disputes.php" class="btn btn-warning btn-block">
                                            <i class="fas fa-exclamation-triangle"></i> Resolve Disputes
                                        </a>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <a href="users.php?action=create" class="btn btn-info btn-block">
                                            <i class="fas fa-user-plus"></i> Add User
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Details</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_activities)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No recent activities</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_activities as $activity): ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                    switch ($activity->type) {
                                                        case 'pledge':
                                                            echo '<span class="badge badge-primary">Pledge</span>';
                                                            break;
                                                        case 'match':
                                                            echo '<span class="badge badge-success">Match</span>';
                                                            break;
                                                        case 'dispute':
                                                            echo '<span class="badge badge-danger">Dispute</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="badge badge-secondary">Other</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>#<?php echo $activity->id; ?></td>
                                                <td><?php echo $activity->user_name; ?></td>
                                                <td>
                                                    <?php
                                                    if ($activity->type == 'pledge' || $activity->type == 'match') {
                                                        echo format_currency($activity->amount, 'Tokens');
                                                    } else {
                                                        echo 'Dispute filed';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo format_date($activity->created_at); ?></td>
                                                <td>
                                                    <?php
                                                    switch ($activity->status) {
                                                        case 'pending':
                                                            echo '<span class="badge badge-warning">Pending</span>';
                                                            break;
                                                        case 'matched':
                                                            echo '<span class="badge badge-info">Matched</span>';
                                                            break;
                                                        case 'payment_sent':
                                                            echo '<span class="badge badge-primary">Payment Sent</span>';
                                                            break;
                                                        case 'completed':
                                                            echo '<span class="badge badge-success">Completed</span>';
                                                            break;
                                                        case 'cancelled':
                                                            echo '<span class="badge badge-secondary">Cancelled</span>';
                                                            break;
                                                        case 'open':
                                                            echo '<span class="badge badge-danger">Open</span>';
                                                            break;
                                                        case 'under_review':
                                                            echo '<span class="badge badge-warning">Under Review</span>';
                                                            break;
                                                        case 'resolved':
                                                            echo '<span class="badge badge-success">Resolved</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="badge badge-secondary">Unknown</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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
</body>
</html>
