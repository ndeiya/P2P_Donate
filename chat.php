<?php
// Set page title
$page_title = 'Chat';

// Start session and get user ID
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'database/db_connect.php';

// Start session
start_session();

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Create database instance
$database = new Database();
$db = $database->getConnection();

// Get user ID
$user_id = $_SESSION['user_id'];

// Process send message form
$message = '';
$message_err = $file_err = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $match_id = isset($_POST['match_id']) ? $_POST['match_id'] : null;

    // Debug information
    error_log("POST data received: " . print_r($_POST, true));
    error_log("Match ID: " . $match_id);

    // Validate message
    if (empty(trim($_POST['message'])) && (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] == UPLOAD_ERR_NO_FILE)) {
        $message_err = 'Please enter a message or attach a file.';
    } else {
        $message = sanitize($_POST['message']);
    }

    // Process file upload if provided
    $attachment = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] != UPLOAD_ERR_NO_FILE) {
        $upload_result = upload_file($_FILES['attachment'], 'uploads/attachments/');

        if (!$upload_result['success']) {
            $file_err = $upload_result['message'];
        } else {
            $attachment = $upload_result['filename'];
        }
    }

    // If no errors, save the message
    if (empty($message_err) && empty($file_err) && $match_id) {
        error_log("Attempting to save message for match ID: " . $match_id);

        $query = "INSERT INTO messages (match_id, sender_id, message, attachment) VALUES (:match_id, :sender_id, :message, :attachment)";
        $stmt = $db->prepare($query);

        $stmt->bindParam(':match_id', $match_id);
        $stmt->bindParam(':sender_id', $user_id);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':attachment', $attachment);

        try {
            if ($stmt->execute()) {
                // Clear form data
                $message = '';
                error_log("Message saved successfully!");

                // Update match updated_at timestamp
                $update_query = "UPDATE matches SET updated_at = NOW() WHERE id = :match_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':match_id', $match_id);
                $update_stmt->execute();

                // Redirect to avoid form resubmission
                header("Location: chat.php?match_id=" . $match_id);
                exit;
            } else {
                error_log("Failed to save message: " . print_r($stmt->errorInfo(), true));
                $message_err = "Failed to send message. Please try again.";
            }
        } catch (Exception $e) {
            error_log("Exception when saving message: " . $e->getMessage());
            $message_err = "An error occurred: " . $e->getMessage();
        }
    }
}

// Include header
require_once 'includes/header.php';

// Check if match ID is provided
$match_id = isset($_GET['match_id']) ? $_GET['match_id'] : null;

// If no match ID is provided, get the most recent match
if (!$match_id) {
    $query = "SELECT id FROM matches WHERE (sender_id = :user_id OR receiver_id = :user_id) ORDER BY updated_at DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $match = $stmt->fetch(PDO::FETCH_OBJ);
        $match_id = $match->id;
    }
}

// Get match details if match ID is available
$match = null;
$other_user = null;
$is_sender = false;

if ($match_id) {
    $query = "SELECT m.*,
              p.amount,
              sender.name as sender_name,
              receiver.name as receiver_name,
              sender.id as sender_id,
              receiver.id as receiver_id
              FROM matches m
              JOIN pledges p ON m.pledge_id = p.id
              JOIN users sender ON m.sender_id = sender.id
              JOIN users receiver ON m.receiver_id = receiver.id
              WHERE m.id = :match_id AND (m.sender_id = :user_id OR m.receiver_id = :user_id)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':match_id', $match_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $match = $stmt->fetch(PDO::FETCH_OBJ);

        // Determine if user is sender or receiver
        $is_sender = ($match->sender_id == $user_id);

        // Get other user details
        $other_user_id = $is_sender ? $match->receiver_id : $match->sender_id;
        $other_user_name = $is_sender ? $match->receiver_name : $match->sender_name;

        $query = "SELECT * FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $other_user_id);
        $stmt->execute();
        $other_user = $stmt->fetch(PDO::FETCH_OBJ);
    }
}

// Reset message error variables if we have a match
if ($match) {
    // If we have a match but had errors in the form, we'll display them
    // The actual form processing is now done before including the header
}

// Get messages for the current match
$messages = [];
if ($match) {
    $query = "SELECT m.*, u.name as sender_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.match_id = :match_id ORDER BY m.created_at ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':match_id', $match_id);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_OBJ);

    // Mark messages as read
    $query = "UPDATE messages SET read_status = 1 WHERE match_id = :match_id AND sender_id != :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':match_id', $match_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
}

// Get user's matches for the sidebar
$query = "SELECT m.id, m.status,
          CASE WHEN m.sender_id = :user_id THEN receiver.name ELSE sender.name END as other_party_name,
          m.updated_at,
          (SELECT COUNT(*) FROM messages WHERE match_id = m.id AND sender_id != :user_id AND read_status = 0) as unread_count
          FROM matches m
          JOIN users sender ON m.sender_id = sender.id
          JOIN users receiver ON m.receiver_id = receiver.id
          WHERE (m.sender_id = :user_id OR m.receiver_id = :user_id)
          ORDER BY m.updated_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user_matches = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Chat</h1>
    </div>

    <div class="row">
        <!-- Matches Sidebar -->
        <div class="col-md-4 col-lg-3 mb-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Your Matches</h5>
                    <button class="btn btn-sm btn-outline-primary d-md-none" type="button" data-toggle="collapse" data-target="#matchesList" aria-expanded="false" aria-controls="matchesList">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div class="list-group list-group-flush collapse d-md-block" id="matchesList">
                    <?php if (empty($user_matches)): ?>
                        <div class="list-group-item text-center">No matches found</div>
                    <?php else: ?>
                        <?php foreach ($user_matches as $m): ?>
                            <a href="chat.php?match_id=<?php echo $m->id; ?>" class="list-group-item list-group-item-action <?php echo ($match_id == $m->id) ? 'active' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo $m->other_party_name; ?></h6>
                                        <small>
                                            <?php
                                            switch ($m->status) {
                                                case 'pending':
                                                    echo 'Waiting for payment';
                                                    break;
                                                case 'payment_sent':
                                                    echo 'Payment sent';
                                                    break;
                                                case 'completed':
                                                    echo 'Completed';
                                                    break;
                                                case 'disputed':
                                                    echo 'Disputed';
                                                    break;
                                                case 'cancelled':
                                                    echo 'Cancelled';
                                                    break;
                                                default:
                                                    echo 'Unknown';
                                            }
                                            ?>
                                        </small>
                                    </div>
                                    <?php if ($m->unread_count > 0): ?>
                                        <span class="badge badge-pill badge-danger"><?php echo $m->unread_count; ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="col-md-8 col-lg-9">
            <?php if ($match): ?>
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <h5 class="mb-0 mr-2">
                                <?php echo $is_sender ? $match->receiver_name : $match->sender_name; ?>
                            </h5>
                            <div class="d-flex align-items-center">
                                <span class="badge badge-light mr-2 d-none d-sm-inline">
                                    Match #<?php echo $match_id; ?>
                                </span>
                                <span class="badge badge-light mr-2">
                                    <?php
                                    switch ($match->status) {
                                        case 'pending':
                                            echo 'Waiting for payment';
                                            break;
                                        case 'payment_sent':
                                            echo 'Payment sent';
                                            break;
                                        case 'completed':
                                            echo 'Completed';
                                            break;
                                        case 'disputed':
                                            echo 'Disputed';
                                            break;
                                        case 'cancelled':
                                            echo 'Cancelled';
                                            break;
                                        default:
                                            echo 'Unknown';
                                    }
                                    ?>
                                </span>
                                <a href="matches.php" class="btn btn-sm btn-light">
                                    <i class="fas fa-arrow-left d-inline d-sm-none"></i>
                                    <span class="d-none d-sm-inline">Back to Matches</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message_err) || !empty($file_err)): ?>
                            <div class="alert alert-danger mb-3">
                                <?php
                                    if (!empty($message_err)) echo $message_err . '<br>';
                                    if (!empty($file_err)) echo $file_err;
                                ?>
                            </div>
                        <?php endif; ?>

                        <div class="chat-container mb-3">
                            <?php if (empty($messages)): ?>
                                <div class="text-center text-muted py-5">
                                    <p>No messages yet. Start the conversation!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <div class="chat-message <?php echo ($msg->sender_id == $user_id) ? 'chat-message-receiver' : 'chat-message-sender'; ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong><?php echo $msg->sender_name; ?></strong>
                                            <small class="chat-message-time"><?php echo format_date($msg->created_at, 'd M, h:i A'); ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo nl2br($msg->message); ?></p>
                                        <?php if (!empty($msg->attachment)): ?>
                                            <div class="mt-2">
                                                <a href="uploads/attachments/<?php echo $msg->attachment; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-paperclip"></i> View Attachment
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($match->status != 'cancelled'): ?>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?match_id=' . $match_id); ?>" method="post" enctype="multipart/form-data" class="message-form">
                                <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
                                <div class="form-group">
                                    <textarea name="message" class="form-control auto-resize <?php echo (!empty($message_err)) ? 'is-invalid' : ''; ?>" rows="2" placeholder="Type your message here..."><?php echo $message; ?></textarea>
                                    <span class="invalid-feedback"><?php echo $message_err; ?></span>
                                </div>
                                <div class="form-row">
                                    <div class="col-sm-8 mb-2 mb-sm-0">
                                        <div class="custom-file">
                                            <input type="file" name="attachment" class="custom-file-input <?php echo (!empty($file_err)) ? 'is-invalid' : ''; ?>" id="attachment">
                                            <label class="custom-file-label" for="attachment">Attach File</label>
                                            <span class="invalid-feedback"><?php echo $file_err; ?></span>
                                        </div>
                                        <small class="form-text text-muted d-none d-sm-block">Accepted: JPG, PNG, PDF. Max: 5MB</small>
                                    </div>
                                    <div class="col-sm-4 text-right">
                                        <button type="submit" name="send_message" class="btn btn-primary btn-block">
                                            <i class="fas fa-paper-plane"></i> <span class="d-none d-sm-inline">Send</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <p class="mb-0">This match has been cancelled. You cannot send messages anymore.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <h5>No chat selected or no matches available</h5>
                        <p>Please select a match from the sidebar or create a new match.</p>
                        <a href="matches.php" class="btn btn-primary">Go to Matches</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-scroll to bottom of chat
    var chatContainer = $('.chat-container');
    if (chatContainer.length) {
        chatContainer.scrollTop(chatContainer[0].scrollHeight);
    }

    // Custom file input label
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        if (fileName) {
            $(this).next('.custom-file-label').html(fileName);
        } else {
            $(this).next('.custom-file-label').html('Attach File');
        }
    });

    // Auto-resize textarea
    $('.auto-resize').each(function() {
        this.setAttribute('style', 'height:' + (this.scrollHeight) + 'px;overflow-y:hidden;');
    }).on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    // Show matches list on mobile when a match is selected
    if ($(window).width() < 768 && window.location.search.includes('match_id')) {
        $('#matchesList').collapse('hide');
    }

    // Improve mobile experience
    if ($(window).width() < 768) {
        // Focus on message input when chat is opened
        $('textarea[name="message"]').focus();

        // Collapse matches list after selecting a match
        $('.list-group-item').on('click', function() {
            $('#matchesList').collapse('hide');
        });
    }
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>
