<?php
// Include configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../database/db_connect.php';

// Create database instance
$database = new Database();
$db = $database->getConnection();

echo "Updating disputes table schema...\n";

// First, check if the table exists
$query = "SHOW TABLES LIKE 'disputes'";
$stmt = $db->prepare($query);
$stmt->execute();
$table_exists = $stmt->rowCount() > 0;

if (!$table_exists) {
    echo "Disputes table does not exist. Please run the main schema.sql file first.\n";
    exit;
}

// Alter the status enum to include the new values
$query = "ALTER TABLE disputes MODIFY COLUMN status ENUM('open', 'under_review', 'resolved', 'resolved_sender', 'resolved_receiver', 'cancelled', 'closed') DEFAULT 'open'";
$stmt = $db->prepare($query);

if ($stmt->execute()) {
    echo "Successfully updated disputes table schema.\n";
} else {
    echo "Error updating disputes table schema: " . print_r($stmt->errorInfo(), true) . "\n";
}

// Add resolved_by and resolved_at columns if they don't exist
$query = "SHOW COLUMNS FROM disputes LIKE 'resolved_by'";
$stmt = $db->prepare($query);
$stmt->execute();
$column_exists = $stmt->rowCount() > 0;

if (!$column_exists) {
    // First add the column without the foreign key
    $query = "ALTER TABLE disputes ADD COLUMN resolved_by INT NULL";
    $stmt = $db->prepare($query);

    if ($stmt->execute()) {
        echo "Added resolved_by column to disputes table.\n";

        // Now add the foreign key
        $query = "ALTER TABLE disputes ADD CONSTRAINT fk_disputes_resolved_by FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL";
        $stmt = $db->prepare($query);

        if ($stmt->execute()) {
            echo "Added foreign key constraint for resolved_by column.\n";
        } else {
            echo "Error adding foreign key constraint: " . print_r($stmt->errorInfo(), true) . "\n";
        }
    } else {
        echo "Error adding resolved_by column: " . print_r($stmt->errorInfo(), true) . "\n";
    }
}

$query = "SHOW COLUMNS FROM disputes LIKE 'resolved_at'";
$stmt = $db->prepare($query);
$stmt->execute();
$column_exists = $stmt->rowCount() > 0;

if (!$column_exists) {
    $query = "ALTER TABLE disputes ADD COLUMN resolved_at TIMESTAMP NULL";
    $stmt = $db->prepare($query);

    if ($stmt->execute()) {
        echo "Added resolved_at column to disputes table.\n";
    } else {
        echo "Error adding resolved_at column: " . print_r($stmt->errorInfo(), true) . "\n";
    }
}

echo "Database update completed.\n";
?>
