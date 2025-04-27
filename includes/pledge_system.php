<?php
/**
 * Pledge System Functions
 *
 * This file contains functions for the "give one, receive two" pledge system
 * where users make a fixed GHS pledge and receive two pledges in return.
 *
 * Note: PLEDGE_AMOUNT is defined in config/config.php
 */

// Check if PLEDGE_AMOUNT is defined, if not, define it with a default value
if (!defined('PLEDGE_AMOUNT')) {
    // This is a fallback in case config/config.php is not included before this file
    require_once __DIR__ . '/../config/config.php';
}

/**
 * Create a new pledge for a user
 *
 * @param PDO $db Database connection
 * @param int $user_id User ID
 * @return array Result with status and message
 */
function create_pledge($db, $user_id) {
    // Platform fee in tokens
    $platform_fee = 10;

    // Check if user exists and has enough tokens
    $query = "SELECT * FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$user) {
        return [
            'status' => false,
            'message' => 'User not found'
        ];
    }

    // Check if user has enough tokens for the platform fee
    if ($user->token_balance < $platform_fee) {
        return [
            'status' => false,
            'message' => 'Insufficient tokens. You need ' . $platform_fee . ' tokens to make a pledge.'
        ];
    }

    // Start transaction
    $db->beginTransaction();

    try {
        // Create pledge record
        $query = "INSERT INTO pledges (user_id, amount, status, currency)
                  VALUES (:user_id, :amount, 'pending', 'GHS')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $amount = PLEDGE_AMOUNT; // Create a variable to bind
        $stmt->bindParam(':amount', $amount);
        $stmt->execute();
        $pledge_id = $db->lastInsertId();

        // Deduct platform fee from user's token balance
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

        return [
            'status' => true,
            'message' => 'Pledge created successfully',
            'pledge_id' => $pledge_id
        ];
    } catch (Exception $e) {
        // Rollback transaction
        $db->rollBack();

        return [
            'status' => false,
            'message' => 'Error creating pledge: ' . $e->getMessage()
        ];
    }
}

/**
 * Match a pledger with a receiver
 *
 * @param PDO $db Database connection
 * @param int $pledge_id Pledge ID
 * @return array Result with status and message
 */
function match_pledge($db, $pledge_id) {
    // Get pledge details
    $query = "SELECT * FROM pledges WHERE id = :pledge_id AND status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':pledge_id', $pledge_id);
    $stmt->execute();
    $pledge = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$pledge) {
        return [
            'status' => false,
            'message' => 'Pledge not found or not in pending status'
        ];
    }

    // Find a receiver from the queue
    // Priority is given to users who have made a pledge and are waiting to receive
    $query = "SELECT u.id
              FROM users u
              LEFT JOIN (
                  SELECT receiver_id, COUNT(*) as received_count
                  FROM matches
                  WHERE status = 'completed'
                  GROUP BY receiver_id
              ) m ON u.id = m.receiver_id
              WHERE u.id != :user_id
              AND (m.received_count IS NULL OR m.received_count < 2)
              AND EXISTS (
                  SELECT 1 FROM pledges
                  WHERE user_id = u.id AND status = 'completed'
              )
              ORDER BY m.received_count ASC, RAND()
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $pledge->user_id);
    $stmt->execute();
    $receiver = $stmt->fetch(PDO::FETCH_OBJ);

    // If no eligible receiver found, find any user
    if (!$receiver) {
        $query = "SELECT id FROM users
                  WHERE id != :user_id
                  ORDER BY RAND()
                  LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $pledge->user_id);
        $stmt->execute();
        $receiver = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$receiver) {
            return [
                'status' => false,
                'message' => 'No eligible receiver found'
            ];
        }
    }

    // Start transaction
    $db->beginTransaction();

    try {
        // Update pledge status
        $query = "UPDATE pledges SET status = 'matched' WHERE id = :pledge_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':pledge_id', $pledge_id);
        $stmt->execute();

        // Set deadline (48 hours from now)
        $deadline = date('Y-m-d H:i:s', strtotime('+48 hours'));

        // Create match
        $query = "INSERT INTO matches (pledge_id, sender_id, receiver_id, amount, deadline, currency, status)
                  VALUES (:pledge_id, :sender_id, :receiver_id, :amount, :deadline, 'GHS', 'pending')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':pledge_id', $pledge_id);
        $stmt->bindParam(':sender_id', $pledge->user_id);
        $stmt->bindParam(':receiver_id', $receiver->id);
        $amount = PLEDGE_AMOUNT; // Create a variable to bind
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':deadline', $deadline);
        $stmt->execute();
        $match_id = $db->lastInsertId();

        // Create notifications
        create_notification($pledge->user_id, 'Match Created',
            'You have been matched with a receiver for your pledge of GHS ' . PLEDGE_AMOUNT . '.', 'match', $db);
        create_notification($receiver->id, 'Match Created',
            'You have been matched with a sender for a donation of GHS ' . PLEDGE_AMOUNT . '.', 'match', $db);

        // Commit transaction
        $db->commit();

        return [
            'status' => true,
            'message' => 'Pledge matched successfully',
            'match_id' => $match_id
        ];
    } catch (Exception $e) {
        // Rollback transaction
        $db->rollBack();

        return [
            'status' => false,
            'message' => 'Error matching pledge: ' . $e->getMessage()
        ];
    }
}

/**
 * Confirm a pledge payment
 *
 * @param PDO $db Database connection
 * @param int $match_id Match ID
 * @return array Result with status and message
 */
function confirm_pledge_payment($db, $match_id) {
    // Get match details
    $query = "SELECT * FROM matches WHERE id = :match_id AND status = 'payment_sent'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':match_id', $match_id);
    $stmt->execute();
    $match = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$match) {
        return [
            'status' => false,
            'message' => 'Match not found or payment not sent'
        ];
    }

    // Start transaction
    $db->beginTransaction();

    try {
        // Update match status
        $query = "UPDATE matches SET status = 'completed', updated_at = NOW() WHERE id = :match_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':match_id', $match_id);
        $stmt->execute();

        // Update pledge status
        $query = "UPDATE pledges SET status = 'completed', updated_at = NOW() WHERE id = :pledge_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':pledge_id', $match->pledge_id);
        $stmt->execute();

        // Add sender to receivers queue if not already in queue
        // This is done by updating a field in the users table
        $query = "UPDATE users SET
                  pledges_to_receive = 2,
                  updated_at = NOW()
                  WHERE id = :user_id AND (pledges_to_receive = 0 OR pledges_to_receive IS NULL)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $match->sender_id);
        $stmt->execute();

        // Create notifications
        create_notification($match->sender_id, 'Pledge Completed',
            'Your pledge has been confirmed by the receiver. You are now in queue to receive 2 pledges.', 'pledge', $db);
        create_notification($match->receiver_id, 'Pledge Received',
            'You have confirmed receipt of GHS ' . PLEDGE_AMOUNT . ' from the sender.', 'pledge', $db);

        // Commit transaction
        $db->commit();

        return [
            'status' => true,
            'message' => 'Pledge payment confirmed successfully'
        ];
    } catch (Exception $e) {
        // Rollback transaction
        $db->rollBack();

        return [
            'status' => false,
            'message' => 'Error confirming pledge payment: ' . $e->getMessage()
        ];
    }
}

/**
 * Process a user receiving a pledge
 *
 * @param PDO $db Database connection
 * @param int $match_id Match ID
 * @return array Result with status and message
 */
function process_received_pledge($db, $match_id) {
    // Get match details
    $query = "SELECT * FROM matches WHERE id = :match_id AND status = 'completed'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':match_id', $match_id);
    $stmt->execute();
    $match = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$match) {
        return [
            'status' => false,
            'message' => 'Match not found or not completed'
        ];
    }

    // Start transaction
    $db->beginTransaction();

    try {
        // Get receiver's current pledges_to_receive count
        $query = "SELECT pledges_to_receive FROM users WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $match->receiver_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$user || $user->pledges_to_receive <= 0) {
            // User is not in queue or has received all pledges
            $db->rollBack();
            return [
                'status' => false,
                'message' => 'User is not in queue to receive pledges'
            ];
        }

        // Decrement pledges_to_receive count
        $new_count = $user->pledges_to_receive - 1;
        $query = "UPDATE users SET
                  pledges_to_receive = :new_count,
                  updated_at = NOW()
                  WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':new_count', $new_count);
        $stmt->bindParam(':user_id', $match->receiver_id);
        $stmt->execute();

        // Create notification
        create_notification($match->receiver_id, 'Pledge Received',
            'You have received a pledge of GHS ' . PLEDGE_AMOUNT . '. ' .
            ($new_count > 0 ? "You are still in queue to receive $new_count more pledge(s)." : "You have received all your pledges."),
            'pledge', $db);

        // Commit transaction
        $db->commit();

        return [
            'status' => true,
            'message' => 'Received pledge processed successfully',
            'pledges_remaining' => $new_count
        ];
    } catch (Exception $e) {
        // Rollback transaction
        $db->rollBack();

        return [
            'status' => false,
            'message' => 'Error processing received pledge: ' . $e->getMessage()
        ];
    }
}

/**
 * Get users in the receivers queue
 *
 * @param PDO $db Database connection
 * @return array List of users in queue
 */
function get_receivers_queue($db) {
    $query = "SELECT u.id, u.name, u.email, u.pledges_to_receive
              FROM users u
              WHERE u.pledges_to_receive > 0
              ORDER BY u.updated_at ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

/**
 * Check if a user is eligible to make a pledge
 *
 * @param PDO $db Database connection
 * @param int $user_id User ID
 * @return bool True if eligible, false otherwise
 */
function is_eligible_to_pledge($db, $user_id) {
    // Platform fee in tokens
    $platform_fee = 10;

    // Check if user has any pending or matched pledges
    $query = "SELECT COUNT(*) as count FROM pledges
              WHERE user_id = :user_id AND status IN ('pending', 'matched')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);

    // User is not eligible if they have pending or matched pledges
    if ($result->count > 0) {
        return false;
    }

    // Check if user has enough tokens for the platform fee
    $query = "SELECT token_balance FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_OBJ);

    // User is not eligible if they don't have enough tokens
    if (!$user || $user->token_balance < $platform_fee) {
        return false;
    }

    return true;
}

/**
 * Get pledge statistics for a user
 *
 * @param PDO $db Database connection
 * @param int $user_id User ID
 * @return object Statistics object
 */
function get_user_pledge_stats($db, $user_id) {
    // Get pledges made
    $query = "SELECT COUNT(*) as count FROM pledges
              WHERE user_id = :user_id AND status = 'completed'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $pledges_made = $stmt->fetch(PDO::FETCH_OBJ)->count;

    // Get pledges received
    $query = "SELECT COUNT(*) as count FROM matches
              WHERE receiver_id = :user_id AND status = 'completed'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $pledges_received = $stmt->fetch(PDO::FETCH_OBJ)->count;

    // Get pledges to receive
    $query = "SELECT pledges_to_receive FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_OBJ);
    $pledges_to_receive = $user ? $user->pledges_to_receive : 0;

    return (object) [
        'pledges_made' => $pledges_made,
        'pledges_received' => $pledges_received,
        'pledges_to_receive' => $pledges_to_receive
    ];
}
?>
