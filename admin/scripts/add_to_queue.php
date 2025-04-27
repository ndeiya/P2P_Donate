<?php
// Include configuration
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../database/db_connect.php';
require_once '../../includes/pledge_system.php';

// Create database instance
$database = new Database();
$db = $database->getConnection();

// Email of the user to add to the pledge queue
$email = 'aa04592@gmail.com';

// Check if user exists
$query = "SELECT * FROM users WHERE email = :email";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $email);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_OBJ);

if (!$user) {
    echo "User with email $email not found. Creating user...\n";
    
    // Generate a random password
    $password = bin2hex(random_bytes(8)); // 16 character random password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Create user
    $query = "INSERT INTO users (name, email, password, role, status, mobile_number, mobile_name) 
              VALUES (:name, :email, :password, 'user', 'active', '0123456789', 'Test User')";
    $stmt = $db->prepare($query);
    $name = "User " . substr($email, 0, strpos($email, '@'));
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);
    
    if ($stmt->execute()) {
        $user_id = $db->lastInsertId();
        echo "User created successfully with ID: $user_id\n";
        echo "Generated password: $password (save this for reference)\n";
    } else {
        echo "Error creating user\n";
        exit;
    }
} else {
    $user_id = $user->id;
    echo "User found: {$user->name} (ID: {$user->id})\n";
}

// Check if user is already in queue
if ($user->pledges_to_receive > 0) {
    echo "User is already in queue to receive {$user->pledges_to_receive} pledges.\n";
} else {
    // Add user to queue
    $query = "UPDATE users SET pledges_to_receive = 2 WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        echo "User added to queue to receive 2 pledges successfully!\n";
    } else {
        echo "Error adding user to queue\n";
    }
}

echo "\nDone!\n";
?>
