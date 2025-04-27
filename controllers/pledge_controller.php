<?php
// Include configuration
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../database/db_connect.php';

// Start session
start_session();

// Check if user is logged in
if (!is_logged_in()) {
    redirect('../login.php');
}

// Create database instance
$database = new Database();
$db = $database->getConnection();

// Get user ID
$user_id = $_SESSION['user_id'];

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        case 'cancel':
            // Cancel pledge
            if (isset($_GET['id'])) {
                $pledge_id = $_GET['id'];

                // Get pledge details
                $query = "SELECT * FROM pledges WHERE id = :id AND user_id = :user_id AND status = 'pending'";
                $stmt = $db->prepare($query);

                $stmt->bindParam(':id', $pledge_id);
                $stmt->bindParam(':user_id', $user_id);

                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $pledge = $stmt->fetch(PDO::FETCH_OBJ);

                    // Start transaction
                    $db->beginTransaction();

                    try {
                        // Update pledge status
                        $query = "UPDATE pledges SET status = 'cancelled', updated_at = NOW() WHERE id = :id";
                        $stmt = $db->prepare($query);

                        $stmt->bindParam(':id', $pledge_id);

                        $stmt->execute();

                        // Refund platform fee (10 tokens) to user balance
                        $platform_fee = 10;
                        $query = "UPDATE users SET token_balance = token_balance + :amount WHERE id = :user_id";
                        $stmt = $db->prepare($query);

                        $stmt->bindParam(':amount', $platform_fee);
                        $stmt->bindParam(':user_id', $user_id);

                        $stmt->execute();

                        // Record token transaction
                        $query = "INSERT INTO tokens (user_id, amount, transaction_type, status, reference) VALUES (:user_id, :amount, 'refund', 'confirmed', 'Platform fee refund for cancelled pledge')";
                        $stmt = $db->prepare($query);

                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->bindParam(':amount', $platform_fee);

                        $stmt->execute();

                        // Create notification
                        create_notification($user_id, 'Pledge Cancelled', 'Your pledge of GHS ' . $pledge->amount . ' has been cancelled and the platform fee of 10 tokens has been refunded.', 'pledge', $db);

                        // Commit transaction
                        $db->commit();

                        // Set flash message
                        flash_message('pledge_message', 'Pledge cancelled successfully. Your tokens have been refunded.', 'alert alert-success');
                    } catch (Exception $e) {
                        // Rollback transaction
                        $db->rollBack();

                        // Set flash message
                        flash_message('pledge_message', 'Failed to cancel pledge. Please try again.', 'alert alert-danger');
                    }
                } else {
                    // Set flash message
                    flash_message('pledge_message', 'Invalid pledge or pledge cannot be cancelled.', 'alert alert-danger');
                }
            }
            break;

        default:
            // Invalid action
            flash_message('pledge_message', 'Invalid action.', 'alert alert-danger');
            break;
    }

    // Redirect back to pledges page
    redirect('../pledges.php');
}
?>
