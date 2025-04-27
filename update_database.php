<?php
// Include configuration
require_once 'config/config.php';
require_once 'database/db_connect.php';

// Create database instance
$database = new Database();
$db = $database->getConnection();

echo "Updating database schema...\n";

// Check if the notes column exists in the tokens table
$query = "SHOW COLUMNS FROM tokens LIKE 'notes'";
$stmt = $db->prepare($query);
$stmt->execute();
$column_exists = $stmt->rowCount() > 0;

if (!$column_exists) {
    echo "Adding notes column to tokens table...\n";
    $query = "ALTER TABLE tokens ADD COLUMN notes VARCHAR(255) NULL";
    $stmt = $db->prepare($query);

    if ($stmt->execute()) {
        echo "Column added successfully.\n";
    } else {
        echo "Error adding column: " . print_r($stmt->errorInfo(), true) . "\n";
    }
} else {
    echo "Notes column already exists.\n";
}

echo "Database update completed.\n";
?>
