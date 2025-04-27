<?php
// Set page title
$page_title = 'Referral Management';

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
        case 'complete':
            // Complete a referral manually
            if (isset($_GET['id'])) {
                $referral_id = $_GET['id'];

                // Get referral details
                $query = "SELECT * FROM referrals WHERE id = :id AND status = 'pending'";
                $stmt = $db->query($query);
                $stmt->bindParam(':id', $referral_id);
                $referral = $db->single($stmt);

                if ($referral) {
                    // Start transaction
                    $db->getConnection()->beginTransaction();

                    try {
                        // Update referral status
                        update_referral_status($referral_id, 'completed', $db);

                        // Add bonus tokens to referrer
                        add_bonus_tokens($referral->referrer_id, 10, $db);

                        // Create notification for referrer
                        create_notification(
                            $referral->referrer_id,
                            'Referral Bonus',
                            'You have received 10 bonus tokens for your referral. Bonus tokens can be redeemed when you reach 100 tokens.',
                            'token',
                            $db
                        );

                        // Commit transaction
                        $db->getConnection()->commit();

                        // Set success message
                        $success_message = 'Referral completed successfully.';
                    } catch (Exception $e) {
                        // Rollback transaction
                        $db->getConnection()->rollBack();

                        // Set error message
                        $error_message = 'Failed to complete referral. Error: ' . $e->getMessage();
                    }
                } else {
                    $error_message = 'Invalid referral or referral cannot be completed.';
                }
            }
            break;

        case 'delete':
            // Delete a referral
            if (isset($_GET['id'])) {
                $referral_id = $_GET['id'];

                // Delete referral
                $query = "DELETE FROM referrals WHERE id = :id";
                $stmt = $db->query($query);
                $stmt->bindParam(':id', $referral_id);

                if ($db->execute($stmt)) {
                    $success_message = 'Referral deleted successfully.';
                } else {
                    $error_message = 'Failed to delete referral.';
                }
            }
            break;
    }
}

// Get all referrals
$query = "SELECT r.*, r.status as referral_status,
          referrer.name as referrer_name, referrer.email as referrer_email,
          referred.name as referred_name, referred.email as referred_email,
          referrer.bonus_tokens as referrer_bonus_tokens,
          (SELECT COUNT(*) FROM tokens WHERE user_id = r.referred_id AND transaction_type = 'purchase' AND status = 'confirmed') as has_purchased
          FROM referrals r
          JOIN users referrer ON r.referrer_id = referrer.id
          JOIN users referred ON r.referred_id = referred.id
          ORDER BY r.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$referrals = $stmt->fetchAll(PDO::FETCH_OBJ);

// Count referrals by status
$pending_count = 0;
$completed_count = 0;
$redeemed_count = 0;

foreach ($referrals as $referral) {
    if ($referral->referral_status == 'pending') {
        $pending_count++;
    } elseif ($referral->referral_status == 'completed') {
        $completed_count++;
    } elseif ($referral->referral_status == 'redeemed') {
        $redeemed_count++;
    }
}

// Get top referrers
$query = "SELECT u.id, u.name, u.email, u.bonus_tokens,
          (SELECT COUNT(*) FROM referrals WHERE referrer_id = u.id) as total_referrals,
          (SELECT COUNT(*) FROM referrals WHERE referrer_id = u.id AND status = 'completed') as completed_referrals
          FROM users u
          WHERE (SELECT COUNT(*) FROM referrals WHERE referrer_id = u.id) > 0
          ORDER BY completed_referrals DESC, total_referrals DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$top_referrers = $stmt->fetchAll(PDO::FETCH_OBJ);
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
                    <h1 class="h2">Referral Management</h1>
                </div>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <!-- Referral Stats -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Total Referrals</h6>
                                        <h2 class="mb-0"><?php echo count($referrals); ?></h2>
                                    </div>
                                    <i class="fas fa-user-plus fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Pending Referrals</h6>
                                        <h2 class="mb-0"><?php echo $pending_count; ?></h2>
                                    </div>
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Completed Referrals</h6>
                                        <h2 class="mb-0"><?php echo $completed_count; ?></h2>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Referrers -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Top Referrers</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_referrers)): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">No referrers found.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Total Referrals</th>
                                            <th>Completed Referrals</th>
                                            <th>Bonus Tokens</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_referrers as $referrer): ?>
                                            <tr>
                                                <td><?php echo $referrer->name; ?></td>
                                                <td><?php echo $referrer->email; ?></td>
                                                <td><?php echo $referrer->total_referrals; ?></td>
                                                <td><?php echo $referrer->completed_referrals; ?></td>
                                                <td><?php echo format_currency($referrer->bonus_tokens, 'Tokens'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- All Referrals -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Referrals</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($referrals)): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">No referrals found.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Referrer</th>
                                            <th>Referred User</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($referrals as $referral): ?>
                                            <tr>
                                                <td>#<?php echo $referral->id; ?></td>
                                                <td>
                                                    <?php echo $referral->referrer_name; ?><br>
                                                    <small><?php echo $referral->referrer_email; ?></small>
                                                </td>
                                                <td>
                                                    <?php echo $referral->referred_name; ?><br>
                                                    <small><?php echo $referral->referred_email; ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($referral->referral_status == 'pending'): ?>
                                                        <?php if ($referral->has_purchased > 0): ?>
                                                            <span class="badge badge-warning">Processing</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">Waiting for Purchase</span>
                                                        <?php endif; ?>
                                                    <?php elseif ($referral->referral_status == 'completed'): ?>
                                                        <span class="badge badge-success">Completed</span>
                                                    <?php elseif ($referral->referral_status == 'redeemed'): ?>
                                                        <span class="badge badge-primary">Redeemed</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo format_date($referral->created_at); ?></td>
                                                <td>
                                                    <?php if ($referral->referral_status == 'pending'): ?>
                                                        <a href="referrals.php?action=complete&id=<?php echo $referral->id; ?>" class="btn btn-sm btn-success confirm-complete">
                                                            <i class="fas fa-check"></i> Complete
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="referrals.php?action=delete&id=<?php echo $referral->id; ?>" class="btn btn-sm btn-danger confirm-delete">
                                                        <i class="fas fa-trash"></i> Delete
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
        // Confirm complete referral
        $('.confirm-complete').on('click', function(e) {
            if (!confirm('Are you sure you want to complete this referral? This will award 10 bonus tokens to the referrer.')) {
                e.preventDefault();
            }
        });

        // Confirm delete referral
        $('.confirm-delete').on('click', function(e) {
            if (!confirm('Are you sure you want to delete this referral? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>
