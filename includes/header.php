<?php
// Include configuration
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'database/db_connect.php';

// Start session
start_session();

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Create database instance
$database = new Database();
$db = $database->getConnection();

// Get user data
$user_id = $_SESSION['user_id'];
$user = get_user($user_id, $db);

// Get unread notifications count
$unread_notifications = count_unread_notifications($user_id, $db);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><?php echo SITE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <div class="nav-link dark-mode-toggle" id="dark-mode-toggle">
                            <i class="fas fa-sun"></i>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="notifications.php">
                            <i class="fas fa-bell"></i>
                            <?php if ($unread_notifications > 0): ?>
                                <span class="badge badge-danger"><?php echo $unread_notifications; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> <?php echo $_SESSION['user_name']; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a>
                            <?php if (is_admin()): ?>
                                <a class="dropdown-item" href="admin/index.php"><i class="fas fa-cog"></i> Admin Panel</a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'wallet.php' ? 'active' : ''; ?>" href="wallet.php">
                                <i class="fas fa-wallet"></i> Wallet
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pledges.php' ? 'active' : ''; ?>" href="pledges.php">
                                <i class="fas fa-hand-holding-usd"></i> Pledges
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'matches.php' ? 'active' : ''; ?>" href="matches.php">
                                <i class="fas fa-exchange-alt"></i> Matches
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : ''; ?>" href="chat.php">
                                <i class="fas fa-comments"></i> Chat
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>" href="notifications.php">
                                <i class="fas fa-bell"></i> Notifications
                                <?php if ($unread_notifications > 0): ?>
                                    <span class="badge badge-danger"><?php echo $unread_notifications; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'referrals.php' ? 'active' : ''; ?>" href="referrals.php">
                                <i class="fas fa-user-plus"></i> Referrals
                                <?php
                                // Get bonus tokens
                                $bonus_tokens = get_bonus_tokens($user_id, $db);
                                if ($bonus_tokens >= 100):
                                ?>
                                    <span class="badge badge-success">Redeem</span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dispute.php' ? 'active' : ''; ?>" href="dispute.php">
                                <i class="fas fa-exclamation-triangle"></i> Dispute Resolution
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="support.php">
                                <i class="fas fa-question-circle"></i> Support
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="legal.php">
                                <i class="fas fa-gavel"></i> Legal
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Mobile Sidebar Toggle Button -->
            <button id="sidebar-toggle" class="sidebar-toggle d-lg-none">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-4">
