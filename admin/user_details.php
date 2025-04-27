<?php
// Set page title
$page_title = 'User Details';

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

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('users.php');
}

$user_id = (int)$_GET['id'];

// Process actions
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'block':
            $query = "UPDATE users SET status = 'blocked' WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $success_message = "User has been blocked successfully.";
            break;

        case 'unblock':
            $query = "UPDATE users SET status = 'active' WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $success_message = "User has been unblocked successfully.";
            break;
    }
}

// Get user details
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_OBJ);

if (!$user) {
    redirect('users.php');
}

// Get user pledges
$query = "SELECT p.*, u.name as user_name
          FROM pledges p
          JOIN users u ON p.user_id = u.id
          WHERE p.user_id = :user_id
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$pledges = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get matches where user is sender or receiver
$query = "SELECT m.*,
          p.amount as pledge_amount,
          sender.name as sender_name,
          receiver.name as receiver_name
          FROM matches m
          JOIN pledges p ON m.pledge_id = p.id
          JOIN users sender ON m.sender_id = sender.id
          JOIN users receiver ON m.receiver_id = receiver.id
          WHERE m.sender_id = :user_id OR m.receiver_id = :user_id
          ORDER BY m.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$matches = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get user transactions
$query = "SELECT * FROM tokens WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get referrals
$query = "SELECT r.*,
          referred.name as referred_name,
          referred.email as referred_email
          FROM referrals r
          JOIN users referred ON r.referred_id = referred.id
          WHERE r.referrer_id = :user_id
          ORDER BY r.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$referrals = $stmt->fetchAll(PDO::FETCH_OBJ);
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
                    <h1 class="h2">User Details</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="users.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Users
                        </a>
                    </div>
                </div>

                <?php if (isset($success_message) && !empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (isset($error_message) && !empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <!-- User Profile -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">User Profile</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>ID</th>
                                        <td>#<?php echo $user->id; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Name</th>
                                        <td><?php echo htmlspecialchars($user->name); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?php echo htmlspecialchars($user->email); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Mobile Number</th>
                                        <td><?php echo htmlspecialchars($user->mobile_number); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Mobile Money Name</th>
                                        <td><?php echo htmlspecialchars($user->mobile_name); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Referral Code</th>
                                        <td><?php echo htmlspecialchars($user->referral_code); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge badge-<?php echo $user->status === 'active' ? 'success' : ($user->status === 'blocked' ? 'danger' : 'warning'); ?>">
                                                <?php echo ucfirst($user->status); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Role</th>
                                        <td><?php echo ucfirst($user->role); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Token Balance</th>
                                        <td><?php echo number_format($user->token_balance, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Bonus Tokens</th>
                                        <td><?php echo number_format($user->bonus_tokens, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Registered</th>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($user->created_at)); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Last Updated</th>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($user->updated_at)); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="mt-3">
                            <form method="POST" class="d-inline">
                                <?php if ($user->status === 'active'): ?>
                                    <button type="submit" name="action" value="block" class="btn btn-danger confirm-block">
                                        <i class="fas fa-ban"></i> Block User
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="unblock" class="btn btn-success confirm-unblock">
                                        <i class="fas fa-check"></i> Unblock User
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- User Pledges -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Pledges</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pledges)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No pledges found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($pledges as $pledge): ?>
                                            <tr>
                                                <td><?php echo $pledge->id; ?></td>
                                                <td><?php echo format_currency($pledge->amount, isset($pledge->currency) ? $pledge->currency : 'GHS'); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                        echo $pledge->status === 'completed' ? 'success' :
                                                            ($pledge->status === 'pending' ? 'warning' :
                                                                ($pledge->status === 'cancelled' ? 'danger' : 'info'));
                                                    ?>">
                                                        <?php echo ucfirst($pledge->status); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($pledge->created_at)); ?></td>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($pledge->updated_at)); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Matches -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Matches</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Counterparty</th>
                                        <th>Created</th>
                                        <th>Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($matches)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No matches found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($matches as $match): ?>
                                            <tr>
                                                <td><?php echo $match->id; ?></td>
                                                <td>
                                                    <?php if ($match->sender_id == $user_id): ?>
                                                        <span class="badge badge-primary">Sent</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">Received</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo format_currency($match->amount, isset($match->currency) ? $match->currency : 'GHS'); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                        echo $match->status === 'completed' ? 'success' :
                                                            ($match->status === 'pending' ? 'warning' :
                                                                ($match->status === 'cancelled' ? 'danger' : 'info'));
                                                    ?>">
                                                        <?php echo ucfirst($match->status); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($match->sender_id == $user_id): ?>
                                                        <?php echo htmlspecialchars($match->receiver_name); ?>
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars($match->sender_name); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($match->created_at)); ?></td>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($match->updated_at)); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Token Transactions -->
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
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Reference</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($transactions)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No transactions found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($transactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo $transaction->id; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                        echo $transaction->transaction_type === 'purchase' ? 'primary' :
                                                            ($transaction->transaction_type === 'bonus' ? 'success' : 'info');
                                                    ?>">
                                                        <?php echo ucfirst($transaction->transaction_type); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($transaction->amount, 2); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                        echo $transaction->status === 'confirmed' ? 'success' :
                                                            ($transaction->status === 'pending' ? 'warning' : 'danger');
                                                    ?>">
                                                        <?php echo ucfirst($transaction->status); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($transaction->reference); ?></td>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($transaction->created_at)); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Referrals -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Referrals</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Referred User</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($referrals)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No referrals found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($referrals as $referral): ?>
                                            <tr>
                                                <td><?php echo $referral->id; ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($referral->referred_name); ?>
                                                    (<?php echo htmlspecialchars($referral->referred_email); ?>)
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                        echo $referral->status === 'completed' ? 'success' :
                                                            ($referral->status === 'pending' ? 'warning' : 'info');
                                                    ?>">
                                                        <?php echo ucfirst($referral->status); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($referral->created_at)); ?></td>
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

    <script>
    $(document).ready(function() {
        // Confirm block user
        $('.confirm-block').on('click', function(e) {
            if (!confirm('Are you sure you want to block this user? They will not be able to login or use the system.')) {
                e.preventDefault();
            }
        });

        // Confirm unblock user
        $('.confirm-unblock').on('click', function(e) {
            if (!confirm('Are you sure you want to unblock this user?')) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>
