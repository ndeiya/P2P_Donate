<?php
// Include configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

// Create database instance
$database = new Database();
$db = $database->getConnection();

echo "Testing dispute resolution...\n";

// First, check if there are any open disputes
$query = "SELECT d.*, m.sender_id, m.receiver_id, m.status as match_status
          FROM disputes d
          JOIN matches m ON d.match_id = m.id
          WHERE d.status = 'open'
          LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute();
$dispute = $stmt->fetch(PDO::FETCH_OBJ);

if (!$dispute) {
    echo "No open disputes found. Creating a test dispute...\n";

    // Find a match to create a dispute for
    $query = "SELECT * FROM matches WHERE status = 'pending' OR status = 'payment_sent' LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $match = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$match) {
        echo "No suitable matches found to create a dispute. Exiting.\n";
        exit;
    }

    // Create a test dispute
    $query = "INSERT INTO disputes (match_id, user_id, reason, status)
              VALUES (:match_id, :user_id, :reason, 'open')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':match_id', $match->id);
    $stmt->bindParam(':user_id', $match->sender_id);
    $reason = "Test dispute for debugging purposes";
    $stmt->bindParam(':reason', $reason);

    if ($stmt->execute()) {
        $dispute_id = $db->lastInsertId();
        echo "Created test dispute with ID: $dispute_id\n";

        // Update match status
        $query = "UPDATE matches SET status = 'disputed' WHERE id = :match_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':match_id', $match->id);
        $stmt->execute();

        // Get the created dispute
        $query = "SELECT d.*, m.sender_id, m.receiver_id, m.status as match_status
                  FROM disputes d
                  JOIN matches m ON d.match_id = m.id
                  WHERE d.id = :dispute_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':dispute_id', $dispute_id);
        $stmt->execute();
        $dispute = $stmt->fetch(PDO::FETCH_OBJ);
    } else {
        echo "Failed to create test dispute: " . print_r($stmt->errorInfo(), true) . "\n";
        exit;
    }
}

echo "Found dispute ID: {$dispute->id} for match ID: {$dispute->match_id}\n";

// Test resolving the dispute
echo "Testing dispute resolution...\n";

// Start transaction
$db->beginTransaction();

try {
    // Find an admin user
    $query = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$admin) {
        throw new Exception("No admin user found in the database");
    }

    $admin_id = $admin->id;
    echo "Using admin ID: $admin_id\n";

    // Update dispute
    $query = "UPDATE disputes SET
             status = :status,
             resolution = :resolution,
             resolved_at = NOW(),
             resolved_by = :admin_id
             WHERE id = :dispute_id";

    $stmt = $db->prepare($query);
    $status = 'resolved_sender';
    $resolution = 'Test resolution - resolved in favor of sender';

    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':resolution', $resolution);
    $stmt->bindParam(':admin_id', $admin_id);
    $stmt->bindParam(':dispute_id', $dispute->id);

    if (!$stmt->execute()) {
        $error_info = $stmt->errorInfo();
        throw new Exception("SQL Error: " . $error_info[2]);
    }

    echo "Updated dispute status to: $status\n";

    // Update match status based on resolution
    $match_status = ($status === 'resolved_sender') ? 'payment_sent' :
                  (($status === 'resolved_receiver') ? 'completed' : 'cancelled');

    $query = "UPDATE matches SET status = :status WHERE id = :match_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $match_status);
    $stmt->bindParam(':match_id', $dispute->match_id);

    if (!$stmt->execute()) {
        $error_info = $stmt->errorInfo();
        throw new Exception("SQL Error (update match): " . $error_info[2]);
    }

    echo "Updated match status to: $match_status\n";

    // Commit transaction
    $db->commit();
    echo "Dispute resolution test completed successfully.\n";
} catch (Exception $e) {
    // Rollback transaction
    $db->rollBack();
    echo "Error resolving dispute: " . $e->getMessage() . "\n";
}

// Check the final status
$query = "SELECT d.*, m.status as match_status
          FROM disputes d
          JOIN matches m ON d.match_id = m.id
          WHERE d.id = :dispute_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':dispute_id', $dispute->id);
$stmt->execute();
$updated_dispute = $stmt->fetch(PDO::FETCH_OBJ);

echo "Final dispute status: {$updated_dispute->status}\n";
echo "Final match status: {$updated_dispute->match_status}\n";

echo "Test completed.\n";
?>
