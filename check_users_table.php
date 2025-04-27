<?php
// Include configuration
require_once 'config/config.php';
require_once 'database/db_connect.php';

// Create database instance
$database = new Database();
$db = $database->getConnection();

// Check users table structure
echo "Checking users table structure...\n";
$query = "DESCRIBE users";
$stmt = $db->prepare($query);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_OBJ);

echo "Users table columns:\n";
foreach ($columns as $column) {
    echo "- {$column->Field} ({$column->Type})\n";
}

// Check if the user with email aa04592@gmail.com is in the queue
echo "\nChecking if user aa04592@gmail.com is in the queue...\n";
$query = "SELECT id, name, pledges_to_receive FROM users WHERE email = 'aa04592@gmail.com'";
$stmt = $db->prepare($query);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_OBJ);

if ($user) {
    echo "User found: {$user->name} (ID: {$user->id})\n";
    echo "Pledges to receive: {$user->pledges_to_receive}\n";
} else {
    echo "User not found\n";
}

echo "\nDone!\n";
?>
