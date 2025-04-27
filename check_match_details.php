<?php
// Include configuration
require_once 'config/config.php';
require_once 'database/db_connect.php';

// Create database instance
$database = new Database();
$db = $database->getConnection();

// Check the latest match
echo "Checking the latest match...\n";
$query = "SELECT m.*, 
          s.name as sender_name, s.email as sender_email,
          r.name as receiver_name, r.email as receiver_email
          FROM matches m
          JOIN users s ON m.sender_id = s.id
          JOIN users r ON m.receiver_id = r.id
          ORDER BY m.id DESC LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute();
$match = $stmt->fetch(PDO::FETCH_OBJ);

if ($match) {
    echo "Match ID: {$match->id}\n";
    echo "Pledge ID: {$match->pledge_id}\n";
    echo "Sender: {$match->sender_name} ({$match->sender_email})\n";
    echo "Receiver: {$match->receiver_name} ({$match->receiver_email})\n";
    echo "Amount: {$match->amount} {$match->currency}\n";
    echo "Status: {$match->status}\n";
    echo "Deadline: {$match->deadline}\n";
} else {
    echo "No matches found\n";
}

echo "\nDone!\n";
?>
