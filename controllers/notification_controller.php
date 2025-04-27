<?php
// Include configuration
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../database/db_connect.php';

// Start session
start_session();

// Check if user is logged in
if (!is_logged_in()) {
    // Return error for AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }

    // Redirect for normal requests
    redirect('../login.php');
}

// Create database instance
$database = new Database();
$db = $database->getConnection();

// Get user ID
$user_id = $_SESSION['user_id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $response = ['success' => false, 'message' => 'Invalid action'];

    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        switch ($action) {
            case 'mark_as_read':
                if (isset($_POST['notification_id'])) {
                    $notification_id = $_POST['notification_id'];

                    // Mark notification as read
                    $query = "UPDATE notifications SET read_status = 1 WHERE id = :id AND user_id = :user_id";
                    $stmt = $db->prepare($query);

                    $stmt->bindParam(':id', $notification_id);
                    $stmt->bindParam(':user_id', $user_id);

                    if ($stmt->execute()) {
                        $response = ['success' => true, 'message' => 'Notification marked as read'];
                    } else {
                        $response = ['success' => false, 'message' => 'Failed to mark notification as read'];
                    }
                }
                break;

            default:
                $response = ['success' => false, 'message' => 'Invalid action'];
                break;
        }
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
