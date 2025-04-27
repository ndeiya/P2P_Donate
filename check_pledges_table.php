<?php
// Include configuration
require_once 'config/config.php';
require_once 'database/db_connect.php';

// Create database instance
$database = new Database();
$db = $database->getConnection();

// Check pledges table structure
echo "Checking pledges table structure...\n";
$query = "DESCRIBE pledges";
$stmt = $db->prepare($query);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_OBJ);

echo "Pledges table columns:\n";
foreach ($columns as $column) {
    echo "- {$column->Field} ({$column->Type})\n";
}

// Check if there are any pledges in the table
echo "\nChecking for existing pledges...\n";
$query = "SELECT COUNT(*) as count FROM pledges";
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);
echo "Total pledges: {$result->count}\n";

echo "\nDone!\n";
?>
