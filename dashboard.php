<?php
// Set page title
$page_title = 'Dashboard';

// Include header
require_once 'includes/header.php';

// Include pledge system functions
require_once 'includes/pledge_system.php';

// Get user token balance and bonus tokens
$token_balance = get_token_balance($user_id, $db);
$bonus_tokens = get_bonus_tokens($user_id, $db);

// Get user's referral code and pledges to receive
$query = "SELECT referral_code, pledges_to_receive FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user_data = $stmt->fetch(PDO::FETCH_OBJ);
$referral_code = $user_data->referral_code;
$pledges_to_receive = $user_data->pledges_to_receive ?? 0;

// Get user pledge stats
$pledge_stats = get_user_pledge_stats($db, $user_id);

// Get active pledges count
$query = "SELECT COUNT(*) as count FROM pledges WHERE user_id = :user_id AND status IN ('pending', 'matched')";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);
$active_pledges = $result->count;

// Get active matches count
$query = "SELECT COUNT(*) as count FROM matches WHERE (sender_id = :user_id OR receiver_id = :user_id) AND status IN ('pending', 'payment_sent')";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);
$active_matches = $result->count;

// Get recent notifications
$query = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$recent_notifications = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
    </div>

    <!-- Welcome Header -->
    <div class="jumbotron bg-light">
        <h2>Welcome, <?php echo $_SESSION['user_name']; ?>!</h2>
        <p class="lead">Your current token balance: <strong><?php echo format_currency($token_balance, 'Tokens'); ?></strong></p>
        <p>Status: <span class="badge badge-success">Active</span></p>
    </div>

    <!-- Quick Access Cards -->
    <div class="row">
        <div class="col-md-4">
            <div class="card quick-access-card">
                <div class="card-body">
                    <i class="fas fa-hand-holding-usd"></i>
                    <h5 class="card-title">Make a Pledge</h5>
                    <p class="card-text">Pledge your tokens to help others in the community.</p>
                    <a href="pledges.php" class="btn btn-primary">Go to Pledges</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card quick-access-card">
                <div class="card-body">
                    <i class="fas fa-coins"></i>
                    <h5 class="card-title">Buy Tokens</h5>
                    <p class="card-text">Purchase tokens to participate in the platform.</p>
                    <a href="wallet.php" class="btn btn-primary">Go to Wallet</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card quick-access-card">
                <div class="card-body">
                    <i class="fas fa-exchange-alt"></i>
                    <h5 class="card-title">Active Matches</h5>
                    <p class="card-text">View and manage your current matches.</p>
                    <a href="matches.php" class="btn btn-primary">Go to Matches</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Section -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Your Activity Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center mb-3">
                                <h6>Active Pledges</h6>
                                <h2><?php echo $active_pledges; ?></h2>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center mb-3">
                                <h6>Active Matches</h6>
                                <h2><?php echo $active_matches; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <a href="pledges.php" class="btn btn-outline-primary mr-2">View Pledges</a>
                        <a href="matches.php" class="btn btn-outline-primary">View Matches</a>
                    </div>
                </div>
            </div>

            <!-- Pledge System Status -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Pledge System Status</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>How It Works</h6>
                            <p>Make a pledge of <strong>GHS <?php echo PLEDGE_AMOUNT; ?></strong> to help someone, and you'll be placed in queue to receive <strong>two pledges</strong> in return!</p>
                            <div class="d-flex justify-content-between">
                                <div class="text-center">
                                    <h6>Pledges Made</h6>
                                    <h3><?php echo $pledge_stats->pledges_made; ?></h3>
                                </div>
                                <div class="text-center">
                                    <h6>Pledges Received</h6>
                                    <h3><?php echo $pledge_stats->pledges_received; ?></h3>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="pledges.php" class="btn btn-primary">
                                    <i class="fas fa-hand-holding-usd"></i> Make a Pledge
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center">
                                <h6>Your Queue Status</h6>
                                <?php if ($pledges_to_receive > 0): ?>
                                    <div class="alert alert-success">
                                        <h3>You are in the queue!</h3>
                                        <p class="mb-0">You will receive <strong><?php echo $pledges_to_receive; ?></strong> more pledge(s) of GHS <?php echo PLEDGE_AMOUNT; ?> each.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <h3>Not in queue</h3>
                                        <p class="mb-0">Make a pledge to join the queue and receive two pledges in return!</p>
                                    </div>
                                <?php endif; ?>

                                <?php if ($active_pledges > 0): ?>
                                    <div class="alert alert-warning mt-2">
                                        <p class="mb-0">You have <?php echo $active_pledges; ?> active pledge(s) in progress.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Referral Program -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Referral Program</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Earn Bonus Tokens!</h6>
                            <p>Invite friends and earn 10 bonus tokens for each friend who joins and makes their first token purchase.</p>
                            <p><strong>Your Referral Code:</strong> <?php echo $referral_code; ?></p>
                            <a href="referrals.php" class="btn btn-success">
                                <i class="fas fa-user-plus"></i> Invite Friends
                            </a>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center">
                                <h6>Your Bonus Tokens</h6>
                                <h2><?php echo format_currency($bonus_tokens, 'Tokens'); ?></h2>
                                <?php if ($bonus_tokens >= 100): ?>
                                    <div class="alert alert-success mt-2">
                                        <p class="mb-0">You can now redeem your bonus tokens!</p>
                                    </div>
                                    <a href="referrals.php" class="btn btn-outline-success">Redeem Now</a>
                                <?php else: ?>
                                    <div class="progress mt-2">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo min(100, ($bonus_tokens / 100) * 100); ?>%" aria-valuenow="<?php echo $bonus_tokens; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted"><?php echo $bonus_tokens; ?>/100 tokens needed to redeem</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Notifications</h5>
                    <a href="notifications.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($recent_notifications)): ?>
                            <li class="list-group-item text-center">No recent notifications</li>
                        <?php else: ?>
                            <?php foreach ($recent_notifications as $notification): ?>
                                <li class="list-group-item <?php echo $notification->read_status ? '' : 'bg-light'; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo $notification->title; ?></h6>
                                            <p class="mb-1 text-muted"><?php echo $notification->message; ?></p>
                                        </div>
                                        <small class="text-muted"><?php echo format_date($notification->created_at, 'd M'); ?></small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
