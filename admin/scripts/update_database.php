<?php
// Include configuration
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../database/db_connect.php';

// Create database instance
$database = new Database();
$db = $database->getConnection();

echo "Starting database updates for the new pledge system...\n";

// Check if pledges_to_receive column exists in users table
$query = "SHOW COLUMNS FROM users LIKE 'pledges_to_receive'";
$stmt = $db->prepare($query);
$stmt->execute();
$column_exists = $stmt->fetch(PDO::FETCH_OBJ);

if (!$column_exists) {
    echo "Adding pledges_to_receive column to users table...\n";
    $query = "ALTER TABLE users ADD COLUMN pledges_to_receive INT DEFAULT 0";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute()) {
        echo "Column added successfully.\n";
    } else {
        echo "Error adding column: " . print_r($stmt->errorInfo(), true) . "\n";
    }
} else {
    echo "pledges_to_receive column already exists.\n";
}

// Check if currency column exists in pledges table
$query = "SHOW COLUMNS FROM pledges LIKE 'currency'";
$stmt = $db->prepare($query);
$stmt->execute();
$column_exists = $stmt->fetch(PDO::FETCH_OBJ);

if (!$column_exists) {
    echo "Adding currency column to pledges table...\n";
    $query = "ALTER TABLE pledges ADD COLUMN currency VARCHAR(10) DEFAULT 'Tokens'";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute()) {
        echo "Column added successfully.\n";
    } else {
        echo "Error adding column: " . print_r($stmt->errorInfo(), true) . "\n";
    }
} else {
    echo "currency column already exists.\n";
}

// Check if currency column exists in matches table
$query = "SHOW COLUMNS FROM matches LIKE 'currency'";
$stmt = $db->prepare($query);
$stmt->execute();
$column_exists = $stmt->fetch(PDO::FETCH_OBJ);

if (!$column_exists) {
    echo "Adding currency column to matches table...\n";
    $query = "ALTER TABLE matches ADD COLUMN currency VARCHAR(10) DEFAULT 'Tokens'";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute()) {
        echo "Column added successfully.\n";
    } else {
        echo "Error adding column: " . print_r($stmt->errorInfo(), true) . "\n";
    }
} else {
    echo "currency column already exists.\n";
}

echo "Database updates completed.\n";
?>
