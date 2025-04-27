<?php
// Include configuration
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../database/db_connect.php';

// Create database instance
$database = new Database();
$db = $database->getConnection();

// Email of the user to add to pledge queue
$email = 'aa04592@gmail.com';
$amount = 100; // Amount of tokens to pledge

// Check if user exists
$query = "SELECT * FROM users WHERE email = :email";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $email);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_OBJ);

$user_id = null;

if ($user) {
    // User exists
    echo "User found: " . $user->name . " (ID: " . $user->id . ")\n";
    $user_id = $user->id;
} else {
    // User doesn't exist, create a new user
    echo "User not found. Creating new user...\n";

    // Generate a random password
    $password = bin2hex(random_bytes(8)); // 16 character random password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Create user
    $query = "INSERT INTO users (name, email, password, role, status, token_balance)
              VALUES (:name, :email, :password, 'user', 'active', 0)";
    $stmt = $db->prepare($query);
    $name = "User " . substr($email, 0, strpos($email, '@'));
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);

    if ($stmt->execute()) {
        $user_id = $db->lastInsertId();
        echo "User created successfully with ID: " . $user_id . "\n";
        echo "Generated password: " . $password . " (save this for reference)\n";
    } else {
        echo "Error creating user\n";
        exit;
    }
}

// Check if user has enough tokens
if ($user && $user->token_balance < $amount) {
    echo "User doesn't have enough tokens. Current balance: " . $user->token_balance . "\n";
    echo "Adding tokens to user account...\n";

    // Add tokens to user account
    $query = "UPDATE users SET token_balance = token_balance + :amount WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':user_id', $user_id);

    if ($stmt->execute()) {
        echo "Added " . $amount . " tokens to user account\n";

        // Record token transaction
        $query = "INSERT INTO tokens (user_id, amount, transaction_type, status, reference)
                  VALUES (:user_id, :amount, 'admin_credit', 'confirmed', 'Admin credit for pledge')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':amount', $amount);
        $stmt->execute();
    } else {
        echo "Error adding tokens to user account\n";
        exit;
    }
}

// Platform fee in tokens
$platform_fee = 10;

// Create pledge
$query = "INSERT INTO pledges (user_id, amount, status, currency) VALUES (:user_id, :amount, 'pending', 'GHS')";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':amount', $amount);

// Start transaction
$db->beginTransaction();

try {
    // Execute pledge creation
    if ($stmt->execute()) {
        $pledge_id = $db->lastInsertId();

        // Deduct platform fee from user balance
        $query = "UPDATE users SET token_balance = token_balance - :fee WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':fee', $platform_fee);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        // Record token transaction
        $query = "INSERT INTO tokens (user_id, amount, transaction_type, status, reference)
                  VALUES (:user_id, :amount, 'pledge', 'confirmed', 'Platform fee for pledge #" . $pledge_id . "')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':amount', $platform_fee);
        $stmt->execute();

        // Commit transaction
        $db->commit();

        echo "Pledge created successfully with ID: " . $pledge_id . "\n";
        echo "User added to pledge queue successfully!\n";
    } else {
        $db->rollBack();
        echo "Error creating pledge\n";
    }
} catch (Exception $e) {
    $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
?>
