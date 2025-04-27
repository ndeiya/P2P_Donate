<?php
// Include configuration
require_once 'config/config.php';
require_once 'database/db_connect.php';

// Create database instance
$database = new Database();
$db = $database->getConnection();

echo "Adding pledges_to_receive column to users table...\n";
$query = "ALTER TABLE users ADD COLUMN pledges_to_receive INT DEFAULT 0";
$stmt = $db->prepare($query);

if ($stmt->execute()) {
    echo "Column added successfully.\n";
} else {
    echo "Error adding column: " . print_r($stmt->errorInfo(), true) . "\n";
}

echo "Done!\n";
?>
