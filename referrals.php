<?php
// Set page title
$page_title = 'Referrals';

// Include header
require_once 'includes/header.php';

// Get user ID
$user_id = $_SESSION['user_id'];

// Process redeem bonus tokens form
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['redeem_bonus'])) {
    // Redeem bonus tokens
    $result = redeem_bonus_tokens($user_id, $db);

    if ($result['success']) {
        $success_message = $result['message'];
    } else {
        $error_message = $result['message'];
    }
}

// Get user's referral code
$query = "SELECT referral_code, bonus_tokens FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user_data = $stmt->fetch(PDO::FETCH_OBJ);
$referral_code = $user_data->referral_code;
$bonus_tokens = $user_data->bonus_tokens;

// Get user's referrals
$query = "SELECT r.*, r.status as referral_status, u.name, u.email, u.created_at as joined_date,
          (SELECT COUNT(*) FROM tokens WHERE user_id = r.referred_id AND transaction_type = 'purchase' AND status = 'confirmed') as has_purchased
          FROM referrals r
          JOIN users u ON r.referred_id = u.id
          WHERE r.referrer_id = :user_id
          ORDER BY r.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$referrals = $stmt->fetchAll(PDO::FETCH_OBJ);

// Count pending and completed referrals
$pending_count = 0;
$completed_count = 0;
foreach ($referrals as $referral) {
    if ($referral->referral_status == 'pending') {
        $pending_count++;
    } elseif ($referral->referral_status == 'completed') {
        $completed_count++;
    }
}

// Get site URL for sharing
$site_url = SITE_URL;
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Referrals</h1>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <!-- Referral Program Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Referral Program</h5>
                </div>
                <div class="card-body">
                    <p>Invite your friends to join <?php echo SITE_NAME; ?> and earn bonus tokens!</p>
                    <ul>
                        <li>You'll receive <strong>10 bonus tokens</strong> for each friend who joins and makes their first token purchase.</li>
                        <li>Bonus tokens can be redeemed when you reach <strong>100 tokens</strong>.</li>
                        <li>Share your referral code or link with friends to start earning!</li>
                    </ul>

                    <div class="alert alert-info">
                        <h6>Your Referral Code</h6>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" value="<?php echo $referral_code; ?>" id="referral-code" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('referral-code')">Copy</button>
                            </div>
                        </div>

                        <h6>Your Referral Link</h6>
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?php echo $site_url; ?>/register.php?ref=<?php echo $referral_code; ?>" id="referral-link" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('referral-link')">Copy</button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($site_url . '/register.php?ref=' . $referral_code); ?>" target="_blank" class="btn btn-primary">
                            <i class="fab fa-facebook-f"></i> Share on Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode('Join ' . SITE_NAME . ' and earn tokens! Use my referral code: ' . $referral_code); ?>&url=<?php echo urlencode($site_url . '/register.php?ref=' . $referral_code); ?>" target="_blank" class="btn btn-info">
                            <i class="fab fa-twitter"></i> Share on Twitter
                        </a>
                        <a href="https://wa.me/?text=<?php echo urlencode('Join ' . SITE_NAME . ' and earn tokens! Use my referral code: ' . $referral_code . ' or register here: ' . $site_url . '/register.php?ref=' . $referral_code); ?>" target="_blank" class="btn btn-success">
                            <i class="fab fa-whatsapp"></i> Share on WhatsApp
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bonus Tokens -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Bonus Tokens</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="text-center mb-3">
                                <h6>Your Bonus Tokens</h6>
                                <h2><?php echo format_currency($bonus_tokens, 'Tokens'); ?></h2>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center mb-3">
                                <h6>Required for Redemption</h6>
                                <h2>100 Tokens</h2>
                            </div>
                        </div>
                    </div>

                    <?php if ($bonus_tokens >= 100): ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <button type="submit" name="redeem_bonus" class="btn btn-success btn-block">
                                <i class="fas fa-exchange-alt"></i> Redeem Bonus Tokens
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <p class="mb-0">You need at least 100 bonus tokens to redeem. Keep inviting friends!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <!-- Referral Stats -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Referral Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <h6>Total Referrals</h6>
                                <h2><?php echo count($referrals); ?></h2>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <h6>Pending</h6>
                                <h2><?php echo $pending_count; ?></h2>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <h6>Completed</h6>
                                <h2><?php echo $completed_count; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Referral List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Your Referrals</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($referrals)): ?>
                        <div class="alert alert-info">
                            <p class="mb-0">You haven't referred anyone yet. Share your referral code to start earning bonus tokens!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Joined</th>
                                        <th>Status</th>
                                        <th>Bonus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($referrals as $referral): ?>
                                        <tr>
                                            <td><?php echo $referral->name; ?></td>
                                            <td><?php echo format_date($referral->joined_date, 'd M Y'); ?></td>
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
                                            <td>
                                                <?php if ($referral->referral_status == 'completed' || $referral->referral_status == 'redeemed'): ?>
                                                    <span class="text-success">+10 Tokens</span>
                                                <?php else: ?>
                                                    <span class="text-muted">Pending</span>
                                                <?php endif; ?>
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
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    var copyText = document.getElementById(elementId);
    copyText.select();
    copyText.setSelectionRange(0, 99999); // For mobile devices
    document.execCommand("copy");

    // Show feedback
    var button = copyText.nextElementSibling.querySelector('button');
    var originalText = button.textContent;
    button.textContent = "Copied!";
    setTimeout(function() {
        button.textContent = originalText;
    }, 2000);
}
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>
