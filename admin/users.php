<?php
// Set page title
$page_title = 'User Management';

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

// Process actions
if (isset($_POST['action'])) {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    switch ($_POST['action']) {
        case 'block':
            $query = "UPDATE users SET status = 'blocked' WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            break;

        case 'unblock':
            $query = "UPDATE users SET status = 'active' WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            break;

        case 'create_user':
            // Initialize variables
            $name = sanitize($_POST['name']);
            $email = sanitize($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            $mobile_money_number = sanitize($_POST['mobile_money_number']);
            $mobile_money_name = sanitize($_POST['mobile_money_name']);
            $token_balance = isset($_POST['token_balance']) ? (float)$_POST['token_balance'] : 0;

            // Validate inputs
            $errors = [];

            if (empty($name)) {
                $errors[] = "Name is required";
            }

            if (empty($email)) {
                $errors[] = "Email is required";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            } else {
                // Check if email already exists
                $check_query = "SELECT id FROM users WHERE email = :email";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(':email', $email);
                $check_stmt->execute();

                if ($check_stmt->rowCount() > 0) {
                    $errors[] = "Email already exists";
                }
            }

            if (empty($password)) {
                $errors[] = "Password is required";
            } elseif (strlen($password) < 6) {
                $errors[] = "Password must be at least 6 characters";
            }

            if ($password !== $confirm_password) {
                $errors[] = "Passwords do not match";
            }

            if (empty($mobile_money_number)) {
                $errors[] = "Mobile Money Number is required";
            }

            if (empty($mobile_money_name)) {
                $errors[] = "Mobile Money Name is required";
            }

            // If no errors, create the user
            if (empty($errors)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $insert_query = "INSERT INTO users (name, email, password, mobile_money_number, mobile_money_name, token_balance, status, role)
                                VALUES (:name, :email, :password, :mobile_money_number, :mobile_money_name, :token_balance, 'active', 'user')";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->bindParam(':name', $name);
                $insert_stmt->bindParam(':email', $email);
                $insert_stmt->bindParam(':password', $hashed_password);
                $insert_stmt->bindParam(':mobile_money_number', $mobile_money_number);
                $insert_stmt->bindParam(':mobile_money_name', $mobile_money_name);
                $insert_stmt->bindParam(':token_balance', $token_balance);

                if ($insert_stmt->execute()) {
                    $success_message = "User created successfully";
                    // Redirect to users list
                    redirect('users.php');
                } else {
                    $errors[] = "Failed to create user";
                }
            }
            break;
    }
}

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$query = "SELECT * FROM users WHERE role != 'admin'";
if (!empty($search)) {
    $query .= " AND (name LIKE :search OR email LIKE :search)";
}
if (!empty($status_filter)) {
    $query .= " AND status = :status";
}
$query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bindParam(':search', $search_param);
}
if (!empty($status_filter)) {
    $stmt->bindParam(':status', $status_filter);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get total users count for pagination
$count_query = "SELECT COUNT(*) as total FROM users WHERE role != 'admin'";
if (!empty($search)) {
    $count_query .= " AND (name LIKE :search OR email LIKE :search)";
}
if (!empty($status_filter)) {
    $count_query .= " AND status = :status";
}

$count_stmt = $db->prepare($count_query);
if (!empty($search)) {
    $count_stmt->bindParam(':search', $search_param);
}
if (!empty($status_filter)) {
    $count_stmt->bindParam(':status', $status_filter);
}
$count_stmt->execute();
$result = $count_stmt->fetch(PDO::FETCH_OBJ);
$total_users = $result->total;
$total_pages = ceil($total_users / $limit);
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

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">User Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="?action=create" class="btn btn-sm btn-primary">
                            <i class="fas fa-user-plus"></i> Add User
                        </a>
                    </div>
                </div>

                <?php if (isset($_GET['action']) && $_GET['action'] === 'create'): ?>
                <!-- Create User Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Add New User</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="users.php">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Full Name</label>
                                        <input type="text" name="name" id="name" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" name="email" id="email" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">Password</label>
                                        <input type="password" name="password" id="password" class="form-control" required>
                                        <small class="form-text text-muted">Minimum 6 characters</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm Password</label>
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mobile_money_number">Mobile Money Number</label>
                                        <input type="text" name="mobile_money_number" id="mobile_money_number" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mobile_money_name">Mobile Money Name</label>
                                        <input type="text" name="mobile_money_name" id="mobile_money_name" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="token_balance">Initial Token Balance</label>
                                        <input type="number" name="token_balance" id="token_balance" class="form-control" value="0" min="0" step="1">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mt-4">
                                <input type="hidden" name="action" value="create_user">
                                <button type="submit" class="btn btn-primary">Create User</button>
                                <a href="users.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="blocked" <?php echo $status_filter === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="users.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!isset($_GET['action']) || $_GET['action'] !== 'create'): ?>
                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Token Balance</th>
                                        <th>Status</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user->id; ?></td>
                                        <td><?php echo htmlspecialchars($user->name); ?></td>
                                        <td><?php echo htmlspecialchars($user->email); ?></td>
                                        <td><?php echo number_format($user->token_balance, 2); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $user->status === 'active' ? 'success' : ($user->status === 'blocked' ? 'danger' : 'warning'); ?>">
                                                <?php echo ucfirst($user->status); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($user->created_at)); ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user->id; ?>">
                                                <?php if ($user->status === 'active'): ?>
                                                    <button type="submit" name="action" value="block" class="btn btn-sm btn-danger confirm-block">
                                                        <i class="fas fa-ban"></i> Block
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" name="action" value="unblock" class="btn btn-sm btn-success confirm-unblock">
                                                        <i class="fas fa-check"></i> Unblock
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                            <a href="user_details.php?id=<?php echo $user->id; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
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