<?php
// Include configuration
require_once 'config/config.php';
require_once 'database/db_connect.php';

// Create database instance
$database = new Database();
$db = $database->getConnection();

// Add currency column to pledges table
echo "Adding currency column to pledges table...\n";
$query = "ALTER TABLE pledges ADD COLUMN currency VARCHAR(10) DEFAULT 'GHS'";
$stmt = $db->prepare($query);

if ($stmt->execute()) {
    echo "Column added to pledges table successfully.\n";
} else {
    echo "Error adding column to pledges table: " . print_r($stmt->errorInfo(), true) . "\n";
}

// Add currency column to matches table
echo "Adding currency column to matches table...\n";
$query = "ALTER TABLE matches ADD COLUMN currency VARCHAR(10) DEFAULT 'GHS'";
$stmt = $db->prepare($query);

if ($stmt->execute()) {
    echo "Column added to matches table successfully.\n";
} else {
    echo "Error adding column to matches table: " . print_r($stmt->errorInfo(), true) . "\n";
}

echo "Done!\n";
?>
