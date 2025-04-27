<?php
// Define the root path to make includes work from any directory
define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);

// Include configuration
require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'includes/functions.php';
require_once ROOT_PATH . 'database/db_connect.php';

// Start session
start_session();

// Check if user is already logged in
if (is_logged_in()) {
    redirect('dashboard.php');
}

// Initialize variables
$email = $password = '';
$email_err = $password_err = '';

// Process form data when form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Create database instance
    $database = new Database();
    $db = $database->getConnection();

    // Validate email
    if (empty(trim($_POST['email']))) {
        $email_err = 'Please enter your email.';
    } else {
        $email = sanitize($_POST['email']);
    }

    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_err = 'Please enter your password.';
    } else {
        $password = trim($_POST['password']);
    }

    // Check input errors before processing
    if (empty($email_err) && empty($password_err)) {
        // Prepare a select statement
        $query = "SELECT id, name, email, password, role, status FROM users WHERE email = :email";
        $stmt = $db->prepare($query);

        // Bind parameters and execute
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // Check if email exists
        if ($stmt->rowCount() == 1) {
            // Fetch user data
            $user = $stmt->fetch(PDO::FETCH_OBJ);

            // Check if account is active
            if ($user->status != 'active') {
                $password_err = 'Your account is not active. Please contact support.';
            } else {
                // Verify password
                if (password_verify($password, $user->password)) {
                    // Password is correct, start a new session
                    start_session();

                    // Store data in session variables
                    $_SESSION['user_id'] = $user->id;
                    $_SESSION['user_name'] = $user->name;
                    $_SESSION['user_email'] = $user->email;
                    $_SESSION['user_role'] = $user->role;

                    // Create login notification
                    create_notification($user->id, 'Login Successful', 'You have successfully logged in.', 'system', $database);

                    // Redirect user to dashboard
                    redirect('dashboard.php');
                } else {
                    // Password is not valid
                    $password_err = 'The password you entered is not valid.';
                }
            }
        } else {
            // Email doesn't exist
            $email_err = 'No account found with that email.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4><?php echo SITE_NAME; ?> - Login</h4>
                    </div>
                    <div class="card-body">
                        <?php echo flash_message('login_message'); ?>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
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
                                <button type="submit" class="btn btn-primary btn-block">Login</button>
                            </div>
                            <p class="text-center">Don't have an account? <a href="register.php">Register Now</a></p>
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
