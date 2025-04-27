<?php
// Set page title
$page_title = 'Profile';

// Include header
require_once 'includes/header.php';

// Initialize variables
$name = $email = $mobile_number = $mobile_name = '';
$name_err = $email_err = $mobile_number_err = $mobile_name_err = '';
$success_message = '';
$error_message = '';

// Get user data
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user_data = $stmt->fetch(PDO::FETCH_OBJ);

if ($user_data) {
    $name = $user_data->name;
    $email = $user_data->email;
    $mobile_number = $user_data->mobile_number;
    $mobile_name = $user_data->mobile_name;
    $referral_code = $user_data->referral_code;
    $bonus_tokens = $user_data->bonus_tokens;
}

// Process update profile form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    // Validate name
    if (empty(trim($_POST['name']))) {
        $name_err = 'Please enter your name.';
    } else {
        $name = sanitize($_POST['name']);
    }

    // Validate email
    if (empty(trim($_POST['email']))) {
        $email_err = 'Please enter your email.';
    } else {
        $email = sanitize($_POST['email']);

        // Check if email is already taken by another user
        if ($email != $user_data->email) {
            $query = "SELECT id FROM users WHERE email = :email AND id != :id";
            $stmt = $db->prepare($query);

            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $user_id);

            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $email_err = 'This email is already taken.';
            }
        }
    }

    // Validate mobile number (optional)
    if (!empty(trim($_POST['mobile_number']))) {
        $mobile_number = sanitize($_POST['mobile_number']);
    }

    // Validate mobile name (optional)
    if (!empty(trim($_POST['mobile_name']))) {
        $mobile_name = sanitize($_POST['mobile_name']);
    }

    // Check input errors before updating the database
    if (empty($name_err) && empty($email_err) && empty($mobile_number_err) && empty($mobile_name_err)) {
        // Prepare an update statement
        $query = "UPDATE users SET name = :name, email = :email, mobile_number = :mobile_number, mobile_name = :mobile_name WHERE id = :id";
        $stmt = $db->prepare($query);

        // Bind parameters
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':mobile_number', $mobile_number);
        $stmt->bindParam(':mobile_name', $mobile_name);
        $stmt->bindParam(':id', $user_id);

        // Execute the statement
        if ($stmt->execute()) {
            // Update session variables
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            // Set success message
            $success_message = 'Your profile has been updated successfully.';
        } else {
            $error_message = 'Something went wrong. Please try again later.';
        }
    }
}

// Process change password form
$current_password = $new_password = $confirm_password = '';
$current_password_err = $new_password_err = $confirm_password_err = '';
$password_success_message = '';
$password_error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    // Validate current password
    if (empty(trim($_POST['current_password']))) {
        $current_password_err = 'Please enter your current password.';
    } else {
        $current_password = trim($_POST['current_password']);

        // Check if current password is correct
        if (!password_verify($current_password, $user_data->password)) {
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
            $password_success_message = 'Your password has been changed successfully.';

            // Clear form data
            $current_password = $new_password = $confirm_password = '';
        } else {
            $password_error_message = 'Something went wrong. Please try again later.';
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Profile</h1>
    </div>

    <div class="row">
        <div class="col-md-6">
            <!-- User Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">User Information</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>">
                            <span class="invalid-feedback"><?php echo $name_err; ?></span>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                            <span class="invalid-feedback"><?php echo $email_err; ?></span>
                        </div>
                        <div class="form-group">
                            <label>Mobile Money Number</label>
                            <input type="text" name="mobile_number" class="form-control <?php echo (!empty($mobile_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $mobile_number; ?>">
                            <span class="invalid-feedback"><?php echo $mobile_number_err; ?></span>
                            <small class="form-text text-muted">This is the number where you will receive payments.</small>
                        </div>
                        <div class="form-group">
                            <label>Mobile Money Name</label>
                            <input type="text" name="mobile_name" class="form-control <?php echo (!empty($mobile_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $mobile_name; ?>">
                            <span class="invalid-feedback"><?php echo $mobile_name_err; ?></span>
                            <small class="form-text text-muted">The name associated with your mobile money account.</small>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <!-- Security Block -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Security</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($password_success_message)): ?>
                        <div class="alert alert-success"><?php echo $password_success_message; ?></div>
                    <?php endif; ?>

                    <?php if (!empty($password_error_message)): ?>
                        <div class="alert alert-danger"><?php echo $password_error_message; ?></div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" class="form-control <?php echo (!empty($current_password_err)) ? 'is-invalid' : ''; ?>">
                            <span class="invalid-feedback"><?php echo $current_password_err; ?></span>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>">
                            <span class="invalid-feedback"><?php echo $new_password_err; ?></span>
                            <small class="form-text text-muted">Password must be at least 6 characters long.</small>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                            <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Referral Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Referral Information</h5>
                </div>
                <div class="card-body">
                    <p>Share your referral code with friends and earn bonus tokens!</p>

                    <div class="form-group">
                        <label>Your Referral Code</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?php echo $referral_code; ?>" id="referral-code" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('referral-code')">Copy</button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label>Bonus Tokens</label>
                        <p class="mb-0"><?php echo format_currency($bonus_tokens, 'Tokens'); ?></p>
                        <?php if ($bonus_tokens >= 100): ?>
                            <small class="text-success">You have enough bonus tokens to redeem! Visit the <a href="referrals.php">Referrals</a> page.</small>
                        <?php else: ?>
                            <small class="text-muted">You need 100 bonus tokens to redeem. Visit the <a href="referrals.php">Referrals</a> page for more information.</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Account Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Account Actions</h5>
                </div>
                <div class="card-body">
                    <a href="logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
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
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
