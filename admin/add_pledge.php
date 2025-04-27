<?php
// Set page title
$page_title = 'Add User to Pledge Queue';

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

// Initialize variables
$success_message = '';
$error_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_pledge'])) {
    $email = sanitize($_POST['email']);
    $amount = floatval($_POST['amount']);

    // Validate inputs
    if (empty($email)) {
        $error_message = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email format.';
    } elseif ($amount <= 0) {
        $error_message = 'Amount must be greater than zero.';
    } else {
        // Check if user exists
        $query = "SELECT * FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_OBJ);

        $user_id = null;

        if ($user) {
            // User exists
            $user_id = $user->id;
        } else {
            // User doesn't exist, create a new user
            // Generate a random password
            $password = bin2hex(random_bytes(8)); // 16 character random password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Create user
            $query = "INSERT INTO users (name, email, password, role, status, token_balance)
                    VALUES (:name, :email, :password, 'user', 'active', 0)";
            $stmt = $db->prepare($query);
            $name = "User " . substr($email, 0, strpos($email, '@'));
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);

            if ($stmt->execute()) {
                $user_id = $db->lastInsertId();
                $success_message .= "User created successfully with ID: " . $user_id . "<br>";
                $success_message .= "Generated password: " . $password . " (save this for reference)<br>";
            } else {
                $error_message = "Error creating user";
            }
        }

        if ($user_id) {
            // Check if user has enough tokens
            if ($user && $user->token_balance < $amount) {
                // Add tokens to user account
                $query = "UPDATE users SET token_balance = token_balance + :amount WHERE id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':user_id', $user_id);

                if ($stmt->execute()) {
                    $success_message .= "Added " . $amount . " tokens to user account<br>";

                    // Record token transaction
                    $query = "INSERT INTO tokens (user_id, amount, transaction_type, status, reference)
                            VALUES (:user_id, :amount, 'admin_credit', 'confirmed', 'Admin credit for pledge')";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':amount', $amount);
                    $stmt->execute();
                } else {
                    $error_message = "Error adding tokens to user account";
                }
            }

            if (empty($error_message)) {
                // Platform fee in tokens
                $platform_fee = 10;

                // Create pledge
                $query = "INSERT INTO pledges (user_id, amount, status, currency) VALUES (:user_id, :amount, 'pending', 'GHS')";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':amount', $amount);

                // Start transaction
                $db->beginTransaction();

                try {
                    // Execute pledge creation
                    if ($stmt->execute()) {
                        $pledge_id = $db->lastInsertId();

                        // Deduct platform fee from user balance
                        $query = "UPDATE users SET token_balance = token_balance - :fee WHERE id = :user_id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':fee', $platform_fee);
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->execute();

                        // Record token transaction
                        $query = "INSERT INTO tokens (user_id, amount, transaction_type, status, reference)
                                VALUES (:user_id, :amount, 'pledge', 'confirmed', 'Platform fee for pledge #" . $pledge_id . "')";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->bindParam(':amount', $platform_fee);
                        $stmt->execute();

                        // Commit transaction
                        $db->commit();

                        $success_message .= "Pledge created successfully with ID: " . $pledge_id . "<br>";
                        $success_message .= "User added to pledge queue successfully!";
                    } else {
                        $db->rollBack();
                        $error_message = "Error creating pledge";
                    }
                } catch (Exception $e) {
                    $db->rollBack();
                    $error_message = "Error: " . $e->getMessage();
                }
            }
        }
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
                    <h1 class="h2">Add User to Pledge Queue</h1>
                </div>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Add User to Pledge Queue</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" name="email" class="form-control" value="aa04592@gmail.com" required>
                                <small class="form-text text-muted">If the user doesn't exist, a new account will be created.</small>
                            </div>
                            <div class="form-group">
                                <label for="amount">Pledge Amount (Tokens)</label>
                                <input type="number" name="amount" class="form-control" value="100" min="1" step="1" required>
                                <small class="form-text text-muted">If the user doesn't have enough tokens, they will be credited.</small>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="add_pledge" class="btn btn-primary">Add to Pledge Queue</button>
                                <a href="pledges.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
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
