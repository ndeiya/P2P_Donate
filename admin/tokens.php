<?php
// Set page title
$page_title = 'Token Management';

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
        case 'view':
            // View token purchase details
            if (isset($_GET['id'])) {
                $token_id = $_GET['id'];

                // Get token details
                $query = "SELECT t.*, u.name as user_name, u.email
                          FROM tokens t
                          JOIN users u ON t.user_id = u.id
                          WHERE t.id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $token_id);
                $stmt->execute();
                $token = $stmt->fetch(PDO::FETCH_OBJ);

                if (!$token) {
                    $error_message = 'Token purchase not found.';
                }
            } else {
                redirect('tokens.php');
            }
            break;

        case 'approve':
            // Approve token purchase
            if (isset($_GET['id'])) {
                $token_id = $_GET['id'];

                // Get token details
                $query = "SELECT * FROM tokens WHERE id = :id AND transaction_type = 'purchase' AND status = 'pending'";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $token_id);
                $stmt->execute();
                $token = $stmt->fetch(PDO::FETCH_OBJ);

                if ($token) {
                    // Start transaction
                    $db->beginTransaction();

                    try {
                        // Update token status
                        $query = "UPDATE tokens SET status = 'confirmed' WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':id', $token_id);
                        $stmt->execute();

                        // Credit tokens to user
                        $query = "UPDATE users SET token_balance = token_balance + :amount WHERE id = :user_id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':amount', $token->amount);
                        $stmt->bindParam(':user_id', $token->user_id);
                        $stmt->execute();

                        // Create notification
                        create_notification($token->user_id, 'Token Purchase Approved', 'Your token purchase of ' . format_currency($token->amount, 'Tokens') . ' has been approved.', 'token', $db);

                        // Check if this is the user's first token purchase
                        $query = "SELECT COUNT(*) as count FROM tokens
                                  WHERE user_id = :user_id AND transaction_type = 'purchase'
                                  AND status = 'confirmed' AND id != :current_id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':user_id', $token->user_id);
                        $stmt->bindParam(':current_id', $token_id);
                        $stmt->execute();
                        $result = $stmt->fetch(PDO::FETCH_OBJ);
                        $previous_purchases = $result->count;

                        // If this is the first purchase and user was referred, update referral
                        if ($previous_purchases == 0) {
                            // Check if user was referred
                            $query = "SELECT referred_by FROM users WHERE id = :user_id AND referred_by IS NOT NULL";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':user_id', $token->user_id);
                            $stmt->execute();
                            $referrer = $stmt->fetch(PDO::FETCH_OBJ);

                            if ($referrer) {
                                // Get referral record
                                $query = "SELECT id FROM referrals
                                          WHERE referrer_id = :referrer_id AND referred_id = :referred_id
                                          AND status = 'pending'";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':referrer_id', $referrer->referred_by);
                                $stmt->bindParam(':referred_id', $token->user_id);
                                $stmt->execute();
                                $referral = $stmt->fetch(PDO::FETCH_OBJ);

                                if ($referral) {
                                    // Update referral status
                                    update_referral_status($referral->id, 'completed', $db);

                                    // Add bonus tokens to referrer
                                    add_bonus_tokens($referrer->referred_by, 10, $db);

                                    // Create notification for referrer
                                    create_notification(
                                        $referrer->referred_by,
                                        'Referral Bonus',
                                        'You have received 10 bonus tokens for your referral\'s first token purchase. Bonus tokens can be redeemed when you reach 100 tokens.',
                                        'token',
                                        $db
                                    );
                                }
                            }
                        }

                        // Commit transaction
                        $db->commit();

                        // Set success message
                        $success_message = 'Token purchase approved successfully.';
                    } catch (Exception $e) {
                        // Rollback transaction
                        $db->rollBack();

                        // Set error message
                        $error_message = 'Failed to approve token purchase. Error: ' . $e->getMessage();
                    }
                } else {
                    $error_message = 'Invalid token purchase or already processed.';
                }
            }
            break;

        case 'reject':
            // Reject token purchase
            if (isset($_GET['id'])) {
                $token_id = $_GET['id'];

                // Get token details
                $query = "SELECT * FROM tokens WHERE id = :id AND transaction_type = 'purchase' AND status = 'pending'";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $token_id);
                $stmt->execute();
                $token = $stmt->fetch(PDO::FETCH_OBJ);

                if ($token) {
                    // Update token status
                    $query = "UPDATE tokens SET status = 'rejected' WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $token_id);

                    if ($stmt->execute()) {
                        // Create notification
                        create_notification($token->user_id, 'Token Purchase Rejected', 'Your token purchase of ' . format_currency($token->amount, 'Tokens') . ' has been rejected. Please contact support for more information.', 'token', $db);

                        // Set success message
                        $success_message = 'Token purchase rejected successfully.';
                    } else {
                        $error_message = 'Failed to reject token purchase.';
                    }
                } else {
                    $error_message = 'Invalid token purchase or already processed.';
                }
            }
            break;

        case 'credit':
            // Credit tokens to user
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['credit_tokens'])) {
                $credit_user_id = $_POST['user_id'];
                $amount = floatval($_POST['amount']);
                $reason = sanitize($_POST['reason']);

                if ($amount <= 0) {
                    $error_message = 'Amount must be greater than zero.';
                } else {
                    // Start transaction
                    $db->beginTransaction();

                    try {
                        // Credit tokens to user
                        $query = "UPDATE users SET token_balance = token_balance + :amount WHERE id = :user_id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':amount', $amount);
                        $stmt->bindParam(':user_id', $credit_user_id);
                        $stmt->execute();

                        // Record token transaction
                        $query = "INSERT INTO tokens (user_id, amount, transaction_type, status, reference) VALUES (:user_id, :amount, 'admin_credit', 'confirmed', :reason)";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':user_id', $credit_user_id);
                        $stmt->bindParam(':amount', $amount);
                        $stmt->bindParam(':reason', $reason);
                        $stmt->execute();

                        // Create notification
                        create_notification($credit_user_id, 'Tokens Credited', 'You have been credited with ' . format_currency($amount, 'Tokens') . '. Reason: ' . $reason, 'token', $db);

                        // Commit transaction
                        $db->commit();

                        // Set success message
                        $success_message = 'Tokens credited successfully.';
                    } catch (Exception $e) {
                        // Rollback transaction
                        $db->rollBack();

                        // Set error message
                        $error_message = 'Failed to credit tokens. Error: ' . $e->getMessage();
                    }
                }
            }
            break;
    }
}

// Get pending token purchases
$query = "SELECT t.*, u.name as user_name, u.email
          FROM tokens t
          JOIN users u ON t.user_id = u.id
          WHERE t.transaction_type = 'purchase' AND t.status = 'pending'
          ORDER BY t.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$pending_purchases = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get recent token transactions
$query = "SELECT t.*, u.name as user_name
          FROM tokens t
          JOIN users u ON t.user_id = u.id
          ORDER BY t.created_at DESC
          LIMIT 50";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_transactions = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get users for credit form
$query = "SELECT id, name, email, token_balance FROM users WHERE status = 'active' ORDER BY name";
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
                    <h1 class="h2">Token Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="tokens.php?action=credit" class="btn btn-sm btn-primary">
                            <i class="fas fa-coins"></i> Credit Tokens
                        </a>
                    </div>
                </div>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($token) && $token): ?>
                    <!-- View Token Purchase -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Token Purchase Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Purchase Information</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>ID</th>
                                            <td>#<?php echo $token->id; ?></td>
                                        </tr>
                                        <tr>
                                            <th>User</th>
                                            <td><?php echo $token->user_name; ?> (<?php echo $token->email; ?>)</td>
                                        </tr>
                                        <tr>
                                            <th>Amount</th>
                                            <td><?php echo format_currency($token->amount, 'Tokens'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Reference</th>
                                            <td><?php echo $token->reference; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                <?php
                                                switch ($token->status) {
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
                                        </tr>
                                        <tr>
                                            <th>Date</th>
                                            <td><?php echo format_date($token->created_at); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Proof of Payment</h6>
                                    <?php if (!empty($token->proof_file)): ?>
                                        <div class="mb-3">
                                            <a href="../uploads/proofs/<?php echo $token->proof_file; ?>" target="_blank" class="btn btn-outline-primary">
                                                <i class="fas fa-file-alt"></i> View Proof
                                            </a>
                                        </div>
                                        <?php
                                        $file_extension = strtolower(pathinfo($token->proof_file, PATHINFO_EXTENSION));
                                        if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])):
                                        ?>
                                            <img src="../uploads/proofs/<?php echo $token->proof_file; ?>" class="img-fluid img-thumbnail" alt="Proof of Payment">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <p class="mb-0">No proof file uploaded.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($token->status == 'pending'): ?>
                                <div class="mt-3">
                                    <a href="tokens.php?action=approve&id=<?php echo $token->id; ?>" class="btn btn-success confirm-approve">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                    <a href="tokens.php?action=reject&id=<?php echo $token->id; ?>" class="btn btn-danger confirm-reject">
                                        <i class="fas fa-times"></i> Reject
                                    </a>
                                    <a href="tokens.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="mt-3">
                                    <a href="tokens.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif (isset($_GET['action']) && $_GET['action'] == 'credit'): ?>
                    <!-- Credit Tokens Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Credit Tokens to User</h5>
                        </div>
                        <div class="card-body">
                            <form action="tokens.php?action=credit" method="post">
                                <div class="form-group">
                                    <label for="user_id">Select User</label>
                                    <select name="user_id" class="form-control" required>
                                        <option value="">-- Select User --</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?php echo $user->id; ?>"><?php echo $user->name; ?> (<?php echo $user->email; ?>) - Current Balance: <?php echo format_currency($user->token_balance, 'Tokens'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="amount">Amount (Tokens)</label>
                                    <input type="number" name="amount" class="form-control" min="0.01" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label for="reason">Reason</label>
                                    <input type="text" name="reason" class="form-control" required>
                                    <small class="form-text text-muted">Provide a reason for crediting tokens (e.g., "Bonus", "Compensation", etc.)</small>
                                </div>
                                <div class="form-group">
                                    <button type="submit" name="credit_tokens" class="btn btn-primary">Credit Tokens</button>
                                    <a href="tokens.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Pending Token Purchases -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Pending Token Purchases</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_purchases)): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">No pending token purchases found.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Amount</th>
                                            <th>Reference</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_purchases as $purchase): ?>
                                            <tr>
                                                <td>#<?php echo $purchase->id; ?></td>
                                                <td>
                                                    <?php echo $purchase->user_name; ?><br>
                                                    <small><?php echo $purchase->email; ?></small>
                                                </td>
                                                <td><?php echo format_currency($purchase->amount, 'Tokens'); ?></td>
                                                <td><?php echo $purchase->reference; ?></td>
                                                <td><?php echo format_date($purchase->created_at); ?></td>
                                                <td>
                                                    <a href="tokens.php?action=view&id=<?php echo $purchase->id; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <a href="tokens.php?action=approve&id=<?php echo $purchase->id; ?>" class="btn btn-sm btn-success confirm-approve">
                                                        <i class="fas fa-check"></i> Approve
                                                    </a>
                                                    <a href="tokens.php?action=reject&id=<?php echo $purchase->id; ?>" class="btn btn-sm btn-danger confirm-reject">
                                                        <i class="fas fa-times"></i> Reject
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

                <!-- Recent Token Transactions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Token Transactions</h5>
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_transactions)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No transactions found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_transactions as $transaction): ?>
                                            <tr>
                                                <td>#<?php echo $transaction->id; ?></td>
                                                <td><?php echo $transaction->user_name; ?></td>
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
