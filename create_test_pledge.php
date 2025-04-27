<?php
// Include configuration
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'database/db_connect.php';
require_once 'includes/pledge_system.php';

// Create database instance
$database = new Database();
$db = $database->getConnection();

// User ID to create a pledge for
$user_id = 2; // Change this to the ID of a user you want to create a pledge for

// Check if user exists
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_OBJ);

if (!$user) {
    echo "User with ID $user_id not found\n";
    exit;
}

echo "Creating pledge for user: {$user->name} (ID: {$user->id})\n";

// Create the pledge
$result = create_pledge($db, $user_id);

if ($result['status']) {
    echo "Pledge created successfully with ID: {$result['pledge_id']}\n";
    
    // Try to match the pledge
    echo "Attempting to match the pledge...\n";
    $match_result = match_pledge($db, $result['pledge_id']);
    
    if ($match_result['status']) {
        echo "Pledge matched successfully with match ID: {$match_result['match_id']}\n";
    } else {
        echo "Failed to match pledge: {$match_result['message']}\n";
    }
} else {
    echo "Failed to create pledge: {$result['message']}\n";
}

echo "\nDone!\n";
?>
