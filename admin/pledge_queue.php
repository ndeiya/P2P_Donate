<?php
// Set page title
$page_title = 'Pledge Queue Management';

// Include configuration
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../database/db_connect.php';
require_once '../includes/pledge_system.php';

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

// Process actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    switch ($action) {
        case 'add_to_queue':
            if (isset($_GET['user_id'])) {
                $user_id = (int)$_GET['user_id'];
                
                // Check if user exists
                $query = "SELECT * FROM users WHERE id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_OBJ);
                
                if ($user) {
                    // Add user to queue
                    $query = "UPDATE users SET pledges_to_receive = 2 WHERE id = :user_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "User {$user->name} added to queue to receive 2 pledges.";
                    } else {
                        $error_message = "Failed to add user to queue.";
                    }
                } else {
                    $error_message = "User not found.";
                }
            }
            break;
            
        case 'remove_from_queue':
            if (isset($_GET['user_id'])) {
                $user_id = (int)$_GET['user_id'];
                
                // Check if user exists
                $query = "SELECT * FROM users WHERE id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_OBJ);
                
                if ($user) {
                    // Remove user from queue
                    $query = "UPDATE users SET pledges_to_receive = 0 WHERE id = :user_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "User {$user->name} removed from queue.";
                    } else {
                        $error_message = "Failed to remove user from queue.";
                    }
                } else {
                    $error_message = "User not found.";
                }
            }
            break;
            
        case 'adjust_pledges':
            if (isset($_GET['user_id']) && isset($_GET['count'])) {
                $user_id = (int)$_GET['user_id'];
                $count = (int)$_GET['count'];
                
                if ($count < 0) {
                    $error_message = "Pledge count cannot be negative.";
                    break;
                }
                
                // Check if user exists
                $query = "SELECT * FROM users WHERE id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_OBJ);
                
                if ($user) {
                    // Adjust pledges to receive
                    $query = "UPDATE users SET pledges_to_receive = :count WHERE id = :user_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':count', $count);
                    $stmt->bindParam(':user_id', $user_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Updated {$user->name} to receive {$count} pledges.";
                    } else {
                        $error_message = "Failed to update pledge count.";
                    }
                } else {
                    $error_message = "User not found.";
                }
            }
            break;
    }
}

// Get users in queue
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM pledges WHERE user_id = u.id AND status = 'completed') as pledges_made,
          (SELECT COUNT(*) FROM matches WHERE receiver_id = u.id AND status = 'completed') as pledges_received
          FROM users u
          WHERE u.pledges_to_receive > 0
          ORDER BY u.updated_at ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$queue_users = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get users not in queue
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM pledges WHERE user_id = u.id AND status = 'completed') as pledges_made,
          (SELECT COUNT(*) FROM matches WHERE receiver_id = u.id AND status = 'completed') as pledges_received
          FROM users u
          WHERE u.pledges_to_receive = 0 OR u.pledges_to_receive IS NULL
          ORDER BY u.name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$other_users = $stmt->fetchAll(PDO::FETCH_OBJ);
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
                    <h1 class="h2">Pledge Queue Management</h1>
                </div>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <!-- System Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Pledge System Information</h5>
                    </div>
                    <div class="card-body">
                        <p>This system operates on a "give one, receive two" model:</p>
                        <ul>
                            <li>Users make a fixed pledge of <strong>GHS <?php echo PLEDGE_AMOUNT; ?></strong></li>
                            <li>After making a pledge and it's confirmed, users are placed in a queue</li>
                            <li>Users in the queue will receive <strong>two consecutive pledges</strong></li>
                            <li>After receiving two pledges, users are removed from the queue</li>
                            <li>To rejoin the queue, users must make another pledge</li>
                        </ul>
                    </div>
                </div>

                <!-- Users in Queue -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Users in Pledge Queue</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($queue_users)): ?>
                            <div class="alert alert-info">No users are currently in the pledge queue.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Pledges Made</th>
                                            <th>Pledges Received</th>
                                            <th>Pledges to Receive</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($queue_users as $user): ?>
                                            <tr>
                                                <td><?php echo $user->id; ?></td>
                                                <td><?php echo htmlspecialchars($user->name); ?></td>
                                                <td><?php echo htmlspecialchars($user->email); ?></td>
                                                <td><?php echo $user->pledges_made; ?></td>
                                                <td><?php echo $user->pledges_received; ?></td>
                                                <td>
                                                    <form action="pledge_queue.php" method="get" class="form-inline">
                                                        <input type="hidden" name="action" value="adjust_pledges">
                                                        <input type="hidden" name="user_id" value="<?php echo $user->id; ?>">
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" name="count" class="form-control" value="<?php echo $user->pledges_to_receive; ?>" min="0" max="10">
                                                            <div class="input-group-append">
                                                                <button type="submit" class="btn btn-outline-secondary">Update</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </td>
                                                <td>
                                                    <a href="pledge_queue.php?action=remove_from_queue&user_id=<?php echo $user->id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to remove this user from the queue?')">
                                                        <i class="fas fa-times"></i> Remove
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

                <!-- Add User to Queue -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Add User to Queue</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($other_users)): ?>
                            <div class="alert alert-info">No users available to add to the queue.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Pledges Made</th>
                                            <th>Pledges Received</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($other_users as $user): ?>
                                            <tr>
                                                <td><?php echo $user->id; ?></td>
                                                <td><?php echo htmlspecialchars($user->name); ?></td>
                                                <td><?php echo htmlspecialchars($user->email); ?></td>
                                                <td><?php echo $user->pledges_made; ?></td>
                                                <td><?php echo $user->pledges_received; ?></td>
                                                <td>
                                                    <a href="pledge_queue.php?action=add_to_queue&user_id=<?php echo $user->id; ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-plus"></i> Add to Queue
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
</body>
</html>
