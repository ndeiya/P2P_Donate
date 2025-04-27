<?php
// Set page title
$page_title = 'Transactions Log';

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
$search_user = isset($_GET['user']) ? $_GET['user'] : '';
$search_type = isset($_GET['type']) ? $_GET['type'] : '';
$search_status = isset($_GET['status']) ? $_GET['status'] : '';
$search_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$search_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$query = "SELECT t.*, u.name as user_name, u.email
          FROM tokens t
          JOIN users u ON t.user_id = u.id
          WHERE 1=1";

$params = [];

if (!empty($search_user)) {
    $query .= " AND (u.name LIKE :user OR u.email LIKE :user)";
    $params[':user'] = '%' . $search_user . '%';
}

if (!empty($search_type)) {
    $query .= " AND t.transaction_type = :type";
    $params[':type'] = $search_type;
}

if (!empty($search_status)) {
    $query .= " AND t.status = :status";
    $params[':status'] = $search_status;
}

if (!empty($search_date_from)) {
    $query .= " AND DATE(t.created_at) >= :date_from";
    $params[':date_from'] = $search_date_from;
}

if (!empty($search_date_to)) {
    $query .= " AND DATE(t.created_at) <= :date_to";
    $params[':date_to'] = $search_date_to;
}

$query .= " ORDER BY t.created_at DESC LIMIT 100";

// Execute query
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get payment proofs
$query = "SELECT m.id as match_id, m.proof_file, m.transaction_id, m.created_at,
          sender.name as sender_name, receiver.name as receiver_name
          FROM matches m
          JOIN users sender ON m.sender_id = sender.id
          JOIN users receiver ON m.receiver_id = receiver.id
          WHERE m.proof_file IS NOT NULL
          ORDER BY m.created_at DESC
          LIMIT 50";
$stmt = $db->prepare($query);
$stmt->execute();
$payment_proofs = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get token purchase proofs
$query = "SELECT t.id, t.proof_file, t.reference, t.amount, t.created_at, u.name as user_name
          FROM tokens t
          JOIN users u ON t.user_id = u.id
          WHERE t.transaction_type = 'purchase' AND t.proof_file IS NOT NULL
          ORDER BY t.created_at DESC
          LIMIT 50";
$stmt = $db->prepare($query);
$stmt->execute();
$token_proofs = $stmt->fetchAll(PDO::FETCH_OBJ);
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
                    <h1 class="h2">Transactions Log</h1>
                </div>

                <!-- Search Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Search Transactions</h5>
                    </div>
                    <div class="card-body">
                        <form action="transactions.php" method="get">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="user">User</label>
                                        <input type="text" name="user" class="form-control" value="<?php echo $search_user; ?>" placeholder="Name or Email">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="type">Transaction Type</label>
                                        <select name="type" class="form-control">
                                            <option value="">All Types</option>
                                            <option value="purchase" <?php echo ($search_type == 'purchase') ? 'selected' : ''; ?>>Purchase</option>
                                            <option value="pledge" <?php echo ($search_type == 'pledge') ? 'selected' : ''; ?>>Pledge</option>
                                            <option value="refund" <?php echo ($search_type == 'refund') ? 'selected' : ''; ?>>Refund</option>
                                            <option value="admin_credit" <?php echo ($search_type == 'admin_credit') ? 'selected' : ''; ?>>Admin Credit</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select name="status" class="form-control">
                                            <option value="">All Statuses</option>
                                            <option value="pending" <?php echo ($search_status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo ($search_status == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="rejected" <?php echo ($search_status == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="date_from">Date From</label>
                                        <input type="date" name="date_from" class="form-control" value="<?php echo $search_date_from; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="date_to">Date To</label>
                                        <input type="date" name="date_to" class="form-control" value="<?php echo $search_date_to; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                            <a href="transactions.php" class="btn btn-secondary">
                                                <i class="fas fa-sync-alt"></i> Reset
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Transactions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Token Transactions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Amount</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Reference</th>
                                        <th>Date</th>
                                        <th>Proof</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($transactions)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No transactions found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($transactions as $transaction): ?>
                                            <tr>
                                                <td>#<?php echo $transaction->id; ?></td>
                                                <td>
                                                    <?php echo $transaction->user_name; ?><br>
                                                    <small><?php echo $transaction->email; ?></small>
                                                </td>
                                                <td><?php echo format_currency($transaction->amount, 'Tokens'); ?></td>
                                                <td>
                                                    <?php
                                                    switch ($transaction->transaction_type) {
                                                        case 'purchase':
                                                            echo '<span class="badge badge-primary">Purchase</span>';
                                                            break;
                                                        case 'pledge':
                                                            echo '<span class="badge badge-info">Pledge</span>';
                                                            break;
                                                        case 'refund':
                                                            echo '<span class="badge badge-success">Refund</span>';
                                                            break;
                                                        case 'admin_credit':
                                                            echo '<span class="badge badge-warning">Admin Credit</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="badge badge-secondary">Other</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    switch ($transaction->status) {
                                                        case 'pending':
                                                            echo '<span class="badge badge-warning">Pending</span>';
                                                            break;
                                                        case 'confirmed':
                                                            echo '<span class="badge badge-success">Confirmed</span>';
                                                            break;
                                                        case 'rejected':
                                                            echo '<span class="badge badge-danger">Rejected</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="badge badge-secondary">Unknown</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo $transaction->reference; ?></td>
                                                <td><?php echo format_date($transaction->created_at); ?></td>
                                                <td>
                                                    <?php if (!empty($transaction->proof_file) && $transaction->transaction_type == 'purchase'): ?>
                                                        <a href="../uploads/proofs/<?php echo $transaction->proof_file; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-file-alt"></i> View
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
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

                <!-- Payment Proofs -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Match Payment Proofs</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Match ID</th>
                                        <th>Sender</th>
                                        <th>Receiver</th>
                                        <th>Transaction ID</th>
                                        <th>Date</th>
                                        <th>Proof</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($payment_proofs)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No payment proofs found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($payment_proofs as $proof): ?>
                                            <tr>
                                                <td>#<?php echo $proof->match_id; ?></td>
                                                <td><?php echo $proof->sender_name; ?></td>
                                                <td><?php echo $proof->receiver_name; ?></td>
                                                <td><?php echo $proof->transaction_id; ?></td>
                                                <td><?php echo format_date($proof->created_at); ?></td>
                                                <td>
                                                    <a href="../uploads/proofs/<?php echo $proof->proof_file; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-file-alt"></i> View Proof
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Token Purchase Proofs -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Token Purchase Proofs</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Amount</th>
                                        <th>Reference</th>
                                        <th>Date</th>
                                        <th>Proof</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($token_proofs)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No token purchase proofs found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($token_proofs as $proof): ?>
                                            <tr>
                                                <td>#<?php echo $proof->id; ?></td>
                                                <td><?php echo $proof->user_name; ?></td>
                                                <td><?php echo format_currency($proof->amount, 'Tokens'); ?></td>
                                                <td><?php echo $proof->reference; ?></td>
                                                <td><?php echo format_date($proof->created_at); ?></td>
                                                <td>
                                                    <a href="../uploads/proofs/<?php echo $proof->proof_file; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-file-alt"></i> View Proof
                                                    </a>
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
