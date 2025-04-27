<?php
// Include configuration
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'database/db_connect.php';

// Create database instance
$database = new Database();
$db = $database->getConnection();

// Admin user details
$name = 'Abdul Rahaman';
$email = 'aa04592@gmail.com';
$password = 'Violet&6ix';
$mobile_number = '0247439206';
$mobile_name = 'Abdul Rahaman';
$role = 'admin';

// Generate unique referral code
$referral_code = generate_unique_referral_code($db);

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if user already exists
$query = "SELECT id FROM users WHERE email = :email";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $email);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo "User with email {$email} already exists. Please use a different email or update the existing user.";
} else {
    // Prepare an insert statement
    $query = "INSERT INTO users (name, email, password, mobile_number, mobile_name, referral_code, role, status) 
              VALUES (:name, :email, :password, :mobile_number, :mobile_name, :referral_code, :role, 'active')";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':mobile_number', $mobile_number);
    $stmt->bindParam(':mobile_name', $mobile_name);
    $stmt->bindParam(':referral_code', $referral_code);
    $stmt->bindParam(':role', $role);
    
    // Execute the statement
    if ($stmt->execute()) {
        echo "Admin user created successfully!<br>";
        echo "Email: {$email}<br>";
        echo "Password: {$password}<br>";
        echo "Role: {$role}<br>";
        echo "You can now <a href='login.php'>login</a> with these credentials.";
    } else {
        echo "Something went wrong. Could not create admin user.";
    }
}
?>
