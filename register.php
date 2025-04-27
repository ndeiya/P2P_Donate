<?php
// Include configuration
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'database/db_connect.php';

// Start session
start_session();

// Create database instance for global use
$db_global = new Database();

// Check if user is already logged in
if (is_logged_in()) {
    redirect('dashboard.php');
}

// Initialize variables
$name = $email = $password = $confirm_password = $mobile_number = $mobile_name = $referral_code = '';
$name_err = $email_err = $password_err = $confirm_password_err = $mobile_number_err = $mobile_name_err = $referral_code_err = '';

// Check if referral code is provided in URL
if (isset($_GET['ref']) && !empty($_GET['ref'])) {
    $referral_code = sanitize($_GET['ref']);

    // Verify referral code exists
    $referrer = get_referrer_by_code($referral_code, $db_global);
    if (!$referrer) {
        $referral_code = '';
        $referral_code_err = 'Invalid referral code.';
    }
}

// Process form data when form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Create database instance
    $database = new Database();
    $db = $database->getConnection();

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
        // Prepare a select statement
        $email = sanitize($_POST['email']);
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $db->prepare($query);

        // Bind parameters and execute
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // Check if email already exists
        if ($stmt->rowCount() > 0) {
            $email_err = 'This email is already taken.';
        }
    }

    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_err = 'Please enter a password.';
    } elseif (strlen(trim($_POST['password'])) < 6) {
        $password_err = 'Password must have at least 6 characters.';
    } else {
        $password = trim($_POST['password']);
    }

    // Validate confirm password
    if (empty(trim($_POST['confirm_password']))) {
        $confirm_password_err = 'Please confirm password.';
    } else {
        $confirm_password = trim($_POST['confirm_password']);
        if ($password != $confirm_password) {
            $confirm_password_err = 'Password did not match.';
        }
    }

    // Validate mobile number (required)
    if (empty(trim($_POST['mobile_number']))) {
        $mobile_number_err = 'Please enter your mobile money number.';
    } else {
        $mobile_number = sanitize($_POST['mobile_number']);
    }

    // Validate mobile name (required)
    if (empty(trim($_POST['mobile_name']))) {
        $mobile_name_err = 'Please enter your mobile money name.';
    } else {
        $mobile_name = sanitize($_POST['mobile_name']);
    }

    // Validate referral code (optional)
    $referred_by = null;
    if (!empty(trim($_POST['referral_code']))) {
        $referral_code = sanitize($_POST['referral_code']);

        // Check if referral code exists
        $referrer = get_referrer_by_code($referral_code, $db);

        if (!$referrer) {
            $referral_code_err = 'Invalid referral code.';
        } else {
            $referred_by = $referrer->id;
        }
    }

    // Check input errors before inserting into database
    if (empty($name_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($mobile_number_err) && empty($mobile_name_err) && empty($referral_code_err)) {
        // Start transaction
        $db->beginTransaction();

        try {
            // Generate unique referral code for new user
            $new_referral_code = generate_unique_referral_code($db);

            // Prepare an insert statement
            $query = "INSERT INTO users (name, email, password, mobile_number, mobile_name, referral_code, referred_by)
                      VALUES (:name, :email, :password, :mobile_number, :mobile_name, :referral_code, :referred_by)";
            $stmt = $db->prepare($query);

            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Bind parameters
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':mobile_number', $mobile_number);
            $stmt->bindParam(':mobile_name', $mobile_name);
            $stmt->bindParam(':referral_code', $new_referral_code);
            $stmt->bindParam(':referred_by', $referred_by);

            // Execute the statement
            $stmt->execute();
            $new_user_id = $db->lastInsertId();

            // If user was referred, create referral record
            if ($referred_by) {
                create_referral($referred_by, $new_user_id, $db);

                // Create notification for referrer
                create_notification($referred_by, 'New Referral', 'You have a new referral: ' . $name . '. You will receive 10 bonus tokens when they make their first token purchase.', 'system', $db);
            }

            // Commit transaction
            $db->commit();

            // Set flash message
            flash_message('login_message', 'Registration successful! You can now login.', 'alert alert-success');

            // Redirect to login page
            redirect('login.php');
        } catch (Exception $e) {
            // Rollback transaction
            $db->rollBack();

            echo "Something went wrong. Please try again later. Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4><?php echo SITE_NAME; ?> - Register</h4>
                    </div>
                    <div class="card-body">
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
                                <label>Password</label>
                                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                            </div>
                            <div class="form-group">
                                <label>Mobile Money Number</label>
                                <input type="text" name="mobile_number" class="form-control <?php echo (!empty($mobile_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $mobile_number; ?>">
                                <span class="invalid-feedback"><?php echo $mobile_number_err; ?></span>
                            </div>
                            <div class="form-group">
                                <label>Mobile Money Name</label>
                                <input type="text" name="mobile_name" class="form-control <?php echo (!empty($mobile_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $mobile_name; ?>">
                                <span class="invalid-feedback"><?php echo $mobile_name_err; ?></span>
                            </div>
                            <div class="form-group">
                                <label>Referral Code (Optional)</label>
                                <input type="text" name="referral_code" class="form-control <?php echo (!empty($referral_code_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $referral_code; ?>">
                                <span class="invalid-feedback"><?php echo $referral_code_err; ?></span>
                                <small class="form-text text-muted">If you were referred by someone, enter their referral code here.</small>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-block">Register</button>
                            </div>
                            <p class="text-center">Already have an account? <a href="login.php">Login</a></p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
