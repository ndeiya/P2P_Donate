<?php
// Define the root path to make includes work from any directory
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

// Include configuration file if not already included
if (!defined('SITE_NAME')) {
    require_once ROOT_PATH . 'config/config.php';
}

// Start session if not already started
function start_session() {
    if (session_status() == PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

// Check if user is logged in
function is_logged_in() {
    start_session();
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function is_admin() {
    start_session();
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

// Redirect to a specific page
function redirect($page) {
    header('Location: ' . SITE_URL . '/' . $page);
    exit;
}

// Display flash message
function flash_message($name = '', $message = '', $class = 'alert alert-success') {
    start_session();

    // Set message
    if (!empty($name) && !empty($message)) {
        // Unset any existing message
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }
        if (isset($_SESSION[$name . '_class'])) {
            unset($_SESSION[$name . '_class']);
        }

        // Set new message
        $_SESSION[$name] = $message;
        $_SESSION[$name . '_class'] = $class;
    } elseif (empty($message) && !empty($name)) {
        // Display message
        if (isset($_SESSION[$name]) && isset($_SESSION[$name . '_class'])) {
            $message = $_SESSION[$name];
            $class = $_SESSION[$name . '_class'];

            // Unset message after displaying
            unset($_SESSION[$name]);
            unset($_SESSION[$name . '_class']);

            return '<div class="' . $class . '">' . $message . '</div>';
        }
    }

    return '';
}

// Sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Generate random string
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// Format date
function format_date($date, $format = 'd M Y, h:i A') {
    $datetime = new DateTime($date);
    return $datetime->format($format);
}

// Format currency
function format_currency($amount, $currency = 'USDT') {
    return number_format($amount, 2) . ' ' . $currency;
}

// Upload file
function upload_file($file, $directory = UPLOAD_DIR) {
    // Check if directory exists, if not create it
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }

    // Get file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return [
            'success' => false,
            'message' => 'File size exceeds the maximum limit of ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'
        ];
    }

    // Check file extension
    if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
        return [
            'success' => false,
            'message' => 'Invalid file type. Allowed types: ' . implode(', ', ALLOWED_EXTENSIONS)
        ];
    }

    // Generate unique filename
    $new_filename = generate_random_string() . '_' . time() . '.' . $file_extension;
    $target_file = $directory . $new_filename;

    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return [
            'success' => true,
            'filename' => $new_filename,
            'path' => $target_file
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to upload file'
        ];
    }
}

// Get user by ID
function get_user($user_id, $db) {
    $query = "SELECT * FROM users WHERE id = :id";

    // Check if $db is a PDO instance or Database instance
    if ($db instanceof PDO) {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    } else {
        // For Database class, create a params array and pass it to execute
        $params = [
            ':id' => $user_id
        ];
        $stmt = $db->query($query);
        return $db->single($stmt, $params);
    }
}

// Get user token balance
function get_token_balance($user_id, $db) {
    $query = "SELECT token_balance FROM users WHERE id = :id";

    // Check if $db is a PDO instance or Database instance
    if ($db instanceof PDO) {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result ? $result->token_balance : 0;
    } else {
        // For Database class, create a params array and pass it to execute
        $params = [
            ':id' => $user_id
        ];
        $stmt = $db->query($query);
        $result = $db->single($stmt, $params);
        return $result ? $result->token_balance : 0;
    }
}

// Update token balance
function update_token_balance($user_id, $amount, $db) {
    $query = "UPDATE users SET token_balance = token_balance + :amount WHERE id = :id";

    // Check if $db is a PDO instance or Database instance
    if ($db instanceof PDO) {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':id', $user_id);
        return $stmt->execute();
    } else {
        // For Database class, create a params array and pass it to execute
        $params = [
            ':amount' => $amount,
            ':id' => $user_id
        ];
        $stmt = $db->query($query);
        return $db->execute($stmt, $params);
    }
}

// Create notification
function create_notification($user_id, $title, $message, $type, $db) {
    $query = "INSERT INTO notifications (user_id, title, message, type) VALUES (:user_id, :title, :message, :type)";

    // Check if $db is a PDO instance or Database instance
    if ($db instanceof PDO) {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':type', $type);
        return $stmt->execute();
    } else {
        // For Database class, create a params array and pass it to execute
        $params = [
            ':user_id' => $user_id,
            ':title' => $title,
            ':message' => $message,
            ':type' => $type
        ];
        $stmt = $db->query($query);
        return $db->execute($stmt, $params);
    }
}

// Count unread notifications
function count_unread_notifications($user_id, $db) {
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND read_status = 0";

    // Check if $db is a PDO instance or Database instance
    if ($db instanceof PDO) {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result ? $result->count : 0;
    } else {
        // For Database class, create a params array and pass it to execute
        $params = [
            ':user_id' => $user_id
        ];
        $stmt = $db->query($query);
        $result = $db->single($stmt, $params);
        return $result ? $result->count : 0;
    }
}

// Generate a unique referral code
function generate_referral_code($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $referral_code = '';
    for ($i = 0; $i < $length; $i++) {
        $referral_code .= $characters[rand(0, $charactersLength - 1)];
    }
    return $referral_code;
}

// Check if referral code exists
function referral_code_exists($referral_code, $db) {
    $query = "SELECT id FROM users WHERE referral_code = :referral_code";

    // Check if $db is a PDO instance or Database instance
    if ($db instanceof PDO) {
        $stmt = $db->prepare($query);
        // Use bindValue instead of bindParam for immediate binding
        $stmt->bindValue(':referral_code', $referral_code, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    } else {
        // For Database class, create a params array and pass it to execute
        $params = [
            ':referral_code' => $referral_code
        ];
        $stmt = $db->query($query);
        $db->execute($stmt, $params);
        return $db->rowCount($stmt) > 0;
    }
}

// Generate a unique referral code that doesn't exist in the database
function generate_unique_referral_code($db) {
    $referral_code = generate_referral_code();

    // Check if $db is a PDO instance or Database instance
    if ($db instanceof PDO) {
        // Use direct PDO query to check if referral code exists
        $query = "SELECT id FROM users WHERE referral_code = :referral_code";
        $stmt = $db->prepare($query);

        while (true) {
            // Use bindValue instead of bindParam for immediate binding
            $stmt->bindValue(':referral_code', $referral_code, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                break; // Referral code doesn't exist, we can use it
            }

            // Generate a new code and try again
            $referral_code = generate_referral_code();
        }
    } else {
        // Use the Database class method
        while (referral_code_exists($referral_code, $db)) {
            $referral_code = generate_referral_code();
        }
    }

    return $referral_code;
}

// Get referrer by referral code
function get_referrer_by_code($referral_code, $db) {
    $query = "SELECT id FROM users WHERE referral_code = :referral_code";

    // Check if $db is a PDO instance or Database instance
    if ($db instanceof PDO) {
        $stmt = $db->prepare($query);
        // Use bindValue instead of bindParam for immediate binding
        $stmt->bindValue(':referral_code', $referral_code, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    } else {
        // For Database class, create a params array and pass it to execute
        $params = [
            ':referral_code' => $referral_code
        ];
        $stmt = $db->query($query);
        return $db->single($stmt, $params);
    }
}

// Create referral record
function create_referral($referrer_id, $referred_id, $db) {
    $query = "INSERT INTO referrals (referrer_id, referred_id) VALUES (:referrer_id, :referred_id)";

    // Check if $db is a PDO instance or Database instance
    if ($db instanceof PDO) {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':referrer_id', $referrer_id);
        $stmt->bindParam(':referred_id', $referred_id);
        return $stmt->execute();
    } else {
        // For Database class, create a params array and pass it to execute
        $params = [
            ':referrer_id' => $referrer_id,
            ':referred_id' => $referred_id
        ];
        $stmt = $db->query($query);
        return $db->execute($stmt, $params);
    }
}

// Check if user has pending referrals
function has_pending_referrals($user_id, $db) {
    $query = "SELECT COUNT(*) as count FROM referrals WHERE referrer_id = :user_id AND status = 'pending'";

    // Check if $db is a PDO instance or Database instance
    if ($db instanceof PDO) {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result ? $result->count : 0;
    } else {
        // For Database class, create a params array and pass it to execute
        $params = [
            ':user_id' => $user_id
        ];
        $stmt = $db->query($query);
        $result = $db->single($stmt, $params);
        return $result ? $result->count : 0;
    }
}

// Get user's completed referrals
function get_completed_referrals($user_id, $db) {
    $query = "SELECT r.*, u.name as referred_name, u.email as referred_email
              FROM referrals r
              JOIN users u ON r.referred_id = u.id
              WHERE r.referrer_id = :user_id AND r.status = 'completed'";

    // Check if $db is a PDO instance or Database instance
    if ($db instanceof PDO) {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    } else {
        // For Database class, create a params array and pass it to execute
        $params = [
            ':user_id' => $user_id
        ];
        $stmt = $db->query($query);
        return $db->resultSet($stmt, $params);
    }
}

// Get user's total bonus tokens
function get_bonus_tokens($user_id, $db) {
    $query = "SELECT bonus_tokens FROM users WHERE id = :user_id";

    // Check if $db is a PDO instance or Database instance
    if ($db instanceof PDO) {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result ? $result->bonus_tokens : 0;
    } else {
        // For Database class, create a params array and pass it to execute
        $params = [
            ':user_id' => $user_id
        ];
        $stmt = $db->query($query);
        $result = $db->single($stmt, $params);
        return $result ? $result->bonus_tokens : 0;
    }
}

// Update referral status
function update_referral_status($referral_id, $status, $db) {
    $query = "UPDATE referrals SET status = :status, updated_at = NOW() WHERE id = :id";

    // Check if $db is a PDO instance or Database instance
    if ($db instanceof PDO) {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $referral_id);
        return $stmt->execute();
    } else {
        // For Database class, create a params array and pass it to execute
        $params = [
            ':status' => $status,
            ':id' => $referral_id
        ];
        $stmt = $db->query($query);
        return $db->execute($stmt, $params);
    }
}

// Add bonus tokens to user
function add_bonus_tokens($user_id, $amount, $db) {
    $query = "UPDATE users SET bonus_tokens = bonus_tokens + :amount WHERE id = :user_id";

    // Check if $db is a PDO instance or Database instance
    if ($db instanceof PDO) {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    } else {
        // For Database class, create a params array and pass it to execute
        $params = [
            ':amount' => $amount,
            ':user_id' => $user_id
        ];
        $stmt = $db->query($query);
        return $db->execute($stmt, $params);
    }
}

// Redeem bonus tokens
function redeem_bonus_tokens($user_id, $db) {
    // Get user's bonus tokens
    $bonus_tokens = get_bonus_tokens($user_id, $db);

    // Check if user has enough bonus tokens
    if ($bonus_tokens < 100) {
        return [
            'success' => false,
            'message' => 'You need at least 100 bonus tokens to redeem.'
        ];
    }

    // Start transaction
    if ($db instanceof PDO) {
        $db->beginTransaction();
    } else {
        $db->getConnection()->beginTransaction();
    }

    try {
        // Update user's token balance
        $query = "UPDATE users SET token_balance = token_balance + bonus_tokens, bonus_tokens = 0 WHERE id = :user_id";

        if ($db instanceof PDO) {
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
        } else {
            // For Database class, create a params array and pass it to execute
            $params = [
                ':user_id' => $user_id
            ];
            $stmt = $db->query($query);
            $db->execute($stmt, $params);
        }

        // Record token transaction
        $query = "INSERT INTO tokens (user_id, amount, transaction_type, status, reference) VALUES (:user_id, :amount, 'admin_credit', 'confirmed', 'Bonus tokens redemption')";

        if ($db instanceof PDO) {
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':amount', $bonus_tokens);
            $stmt->execute();
        } else {
            // For Database class, create a params array and pass it to execute
            $params = [
                ':user_id' => $user_id,
                ':amount' => $bonus_tokens
            ];
            $stmt = $db->query($query);
            $db->execute($stmt, $params);
        }

        // Create notification
        create_notification($user_id, 'Bonus Tokens Redeemed', 'You have successfully redeemed ' . format_currency($bonus_tokens, 'Tokens') . ' from your bonus tokens.', 'token', $db);

        // Commit transaction
        if ($db instanceof PDO) {
            $db->commit();
        } else {
            $db->getConnection()->commit();
        }

        return [
            'success' => true,
            'message' => 'You have successfully redeemed ' . format_currency($bonus_tokens, 'Tokens') . '.',
            'amount' => $bonus_tokens
        ];
    } catch (Exception $e) {
        // Rollback transaction
        if ($db instanceof PDO) {
            $db->rollBack();
        } else {
            $db->getConnection()->rollBack();
        }

        return [
            'success' => false,
            'message' => 'Failed to redeem bonus tokens. Please try again.'
        ];
    }
}
?>
