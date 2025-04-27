<?php
// Include configuration
require_once 'config/config.php';
require_once 'database/db_connect.php';

// Create database instance
$database = new Database();
$db = $database->getConnection();

// Check matches table structure
echo "Checking matches table structure...\n";
$query = "DESCRIBE matches";
$stmt = $db->prepare($query);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_OBJ);

echo "Matches table columns:\n";
foreach ($columns as $column) {
    echo "- {$column->Field} ({$column->Type})\n";
}

// Check if there are any matches in the table
echo "\nChecking for existing matches...\n";
$query = "SELECT COUNT(*) as count FROM matches";
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);
echo "Total matches: {$result->count}\n";

echo "\nDone!\n";
?>
