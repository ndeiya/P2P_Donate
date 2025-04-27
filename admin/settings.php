<?php
// Set page title
$page_title = 'Admin Settings';

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

// Process change admin credentials form
$current_password = $new_password = $confirm_password = '';
$current_password_err = $new_password_err = $confirm_password_err = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    // Get admin user data
    $query = "SELECT * FROM users WHERE id = :id AND role = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_OBJ);

    // Validate current password
    if (empty(trim($_POST['current_password']))) {
        $current_password_err = 'Please enter your current password.';
    } else {
        $current_password = trim($_POST['current_password']);

        // Check if current password is correct
        if (!password_verify($current_password, $admin->password)) {
            $current_password_err = 'Current password is incorrect.';
        }
    }

    // Validate new password
    if (empty(trim($_POST['new_password']))) {
        $new_password_err = 'Please enter a new password.';
    } elseif (strlen(trim($_POST['new_password'])) < 6) {
        $new_password_err = 'Password must have at least 6 characters.';
    } else {
        $new_password = trim($_POST['new_password']);
    }

    // Validate confirm password
    if (empty(trim($_POST['confirm_password']))) {
        $confirm_password_err = 'Please confirm the password.';
    } else {
        $confirm_password = trim($_POST['confirm_password']);
        if ($new_password != $confirm_password) {
            $confirm_password_err = 'Password did not match.';
        }
    }

    // Check input errors before updating the database
    if (empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err)) {
        // Prepare an update statement
        $query = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $db->prepare($query);

        // Hash the password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Bind parameters
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':id', $user_id);

        // Execute the statement
        if ($stmt->execute()) {
            // Set success message
            $success_message = 'Your password has been changed successfully.';

            // Clear form data
            $current_password = $new_password = $confirm_password = '';
        } else {
            $error_message = 'Something went wrong. Please try again later.';
        }
    }
}

// Process system settings form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $site_name = sanitize($_POST['site_name']);
    $token_rate = floatval($_POST['token_rate']);

    // Get wallet addresses for each network
    $ethereum_address = isset($_POST['ethereum_address']) ? sanitize($_POST['ethereum_address']) : '';
    $bsc_address = isset($_POST['bsc_address']) ? sanitize($_POST['bsc_address']) : '';
    $arbitrum_address = isset($_POST['arbitrum_address']) ? sanitize($_POST['arbitrum_address']) : '';
    $optimism_address = isset($_POST['optimism_address']) ? sanitize($_POST['optimism_address']) : '';

    // Use the Ethereum address as the main wallet address for backward compatibility
    $wallet_address = $ethereum_address;

    // Validate inputs
    if (empty($site_name)) {
        $error_message = 'Site name cannot be empty.';
    } elseif ($token_rate <= 0) {
        $error_message = 'Token rate must be greater than zero.';
    } elseif (empty($ethereum_address) || empty($bsc_address) || empty($arbitrum_address) || empty($optimism_address)) {
        $error_message = 'All wallet addresses are required.';
    } else {
        // Update config.php file
        $config_file = '../config/config.php';
        $config_content = file_get_contents($config_file);

        // Update site name
        $config_content = preg_replace("/define\('SITE_NAME', '(.*)'\);/", "define('SITE_NAME', '$site_name');", $config_content);

        // Update token rate
        $config_content = preg_replace("/define\('TOKEN_RATE', (.*)\);/", "define('TOKEN_RATE', $token_rate);", $config_content);

        // Write updated content back to file
        if (file_put_contents($config_file, $config_content)) {
            // Save wallet addresses to a separate file
            $wallet_file = '../config/wallet.php';
            $wallet_content = "<?php\n";
            $wallet_content .= "// Wallet addresses for token purchases\n";
            $wallet_content .= "define('WALLET_ADDRESS', '$wallet_address'); // Legacy support\n\n";
            $wallet_content .= "// Network-specific wallet addresses\n";
            $wallet_content .= "\$wallet_networks = [\n";
            $wallet_content .= "    'ETHEREUM' => [\n";
            $wallet_content .= "        'name' => 'Ethereum (ERC-20)',\n";
            $wallet_content .= "        'address' => '$ethereum_address',\n";
            $wallet_content .= "        'icon' => 'fab fa-ethereum'\n";
            $wallet_content .= "    ],\n";
            $wallet_content .= "    'BSC' => [\n";
            $wallet_content .= "        'name' => 'Binance Smart Chain (BEP-20)',\n";
            $wallet_content .= "        'address' => '$bsc_address',\n";
            $wallet_content .= "        'icon' => 'fas fa-coins'\n";
            $wallet_content .= "    ],\n";
            $wallet_content .= "    'ARBITRUM' => [\n";
            $wallet_content .= "        'name' => 'Arbitrum',\n";
            $wallet_content .= "        'address' => '$arbitrum_address',\n";
            $wallet_content .= "        'icon' => 'fas fa-network-wired'\n";
            $wallet_content .= "    ],\n";
            $wallet_content .= "    'OPTIMISM' => [\n";
            $wallet_content .= "        'name' => 'Optimism',\n";
            $wallet_content .= "        'address' => '$optimism_address',\n";
            $wallet_content .= "        'icon' => 'fas fa-bolt'\n";
            $wallet_content .= "    ]\n";
            $wallet_content .= "];\n";
            $wallet_content .= "?>";

            if (file_put_contents($wallet_file, $wallet_content)) {
                $success_message = 'System settings updated successfully.';
            } else {
                $error_message = 'Failed to update wallet addresses.';
            }
        } else {
            $error_message = 'Failed to update system settings.';
        }
    }
}

// Get current system settings
$site_name = SITE_NAME;
$token_rate = TOKEN_RATE;
$wallet_address = '';
$ethereum_address = $bsc_address = $arbitrum_address = $optimism_address = '';

// Check if wallet.php exists
if (file_exists('../config/wallet.php')) {
    require_once '../config/wallet.php';
    if (defined('WALLET_ADDRESS')) {
        $wallet_address = WALLET_ADDRESS;
    }

    // Get network-specific wallet addresses
    if (isset($wallet_networks)) {
        $ethereum_address = $wallet_networks['ETHEREUM']['address'] ?? $wallet_address;
        $bsc_address = $wallet_networks['BSC']['address'] ?? $wallet_address;
        $arbitrum_address = $wallet_networks['ARBITRUM']['address'] ?? $wallet_address;
        $optimism_address = $wallet_networks['OPTIMISM']['address'] ?? $wallet_address;
    } else {
        // If network-specific addresses don't exist, use the main address for all
        $ethereum_address = $bsc_address = $arbitrum_address = $optimism_address = $wallet_address;
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

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Admin Settings</h1>
                </div>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <!-- System Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">System Settings</h5>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <div class="form-group">
                                        <label for="site_name">Site Name</label>
                                        <input type="text" name="site_name" class="form-control" value="<?php echo $site_name; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="token_rate">Token Rate (1 Token = ? USDT)</label>
                                        <input type="number" name="token_rate" class="form-control" value="<?php echo $token_rate; ?>" min="0.01" step="0.01" required>
                                    </div>
                                    <div class="form-group">
                                        <label>USDT Wallet Addresses</label>
                                        <small class="form-text text-muted mb-2">These are the wallet addresses where users will send USDT for token purchases.</small>

                                        <div class="card mb-3">
                                            <div class="card-header bg-light p-2">
                                                <h6 class="mb-0"><i class="fab fa-ethereum mr-1"></i> Ethereum (ERC-20)</h6>
                                            </div>
                                            <div class="card-body p-3">
                                                <input type="text" name="ethereum_address" class="form-control" value="<?php echo $ethereum_address; ?>" required>
                                            </div>
                                        </div>

                                        <div class="card mb-3">
                                            <div class="card-header bg-light p-2">
                                                <h6 class="mb-0"><i class="fas fa-coins mr-1"></i> Binance Smart Chain (BEP-20)</h6>
                                            </div>
                                            <div class="card-body p-3">
                                                <input type="text" name="bsc_address" class="form-control" value="<?php echo $bsc_address; ?>" required>
                                            </div>
                                        </div>

                                        <div class="card mb-3">
                                            <div class="card-header bg-light p-2">
                                                <h6 class="mb-0"><i class="fas fa-network-wired mr-1"></i> Arbitrum</h6>
                                            </div>
                                            <div class="card-body p-3">
                                                <input type="text" name="arbitrum_address" class="form-control" value="<?php echo $arbitrum_address; ?>" required>
                                            </div>
                                        </div>

                                        <div class="card mb-3">
                                            <div class="card-header bg-light p-2">
                                                <h6 class="mb-0"><i class="fas fa-bolt mr-1"></i> Optimism</h6>
                                            </div>
                                            <div class="card-body p-3">
                                                <input type="text" name="optimism_address" class="form-control" value="<?php echo $optimism_address; ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" name="update_settings" class="btn btn-primary">Update Settings</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <!-- Change Admin Password -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Change Admin Password</h5>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <div class="form-group">
                                        <label for="current_password">Current Password</label>
                                        <input type="password" name="current_password" class="form-control <?php echo (!empty($current_password_err)) ? 'is-invalid' : ''; ?>">
                                        <span class="invalid-feedback"><?php echo $current_password_err; ?></span>
                                    </div>
                                    <div class="form-group">
                                        <label for="new_password">New Password</label>
                                        <input type="password" name="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>">
                                        <span class="invalid-feedback"><?php echo $new_password_err; ?></span>
                                        <small class="form-text text-muted">Password must be at least 6 characters long.</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm New Password</label>
                                        <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                                        <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- System Information -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">System Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>PHP Version</th>
                                        <td><?php echo phpversion(); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Server Software</th>
                                        <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Database</th>
                                        <td>MySQL</td>
                                    </tr>
                                    <tr>
                                        <th>Upload Max Size</th>
                                        <td><?php echo ini_get('upload_max_filesize'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Post Max Size</th>
                                        <td><?php echo ini_get('post_max_size'); ?></td>
                                    </tr>
                                </table>
                            </div>
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
