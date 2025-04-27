<?php
// Set page title
$page_title = 'Support';

// Include header
require_once 'includes/header.php';

// Initialize variables
$name = $email = $subject = $message = '';
$name_err = $email_err = $subject_err = $message_err = '';
$success_message = '';
$error_message = '';

// Process contact form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_contact'])) {
    // Validate name
    if (empty(trim($_POST['name']))) {
        $name_err = 'Please enter your name.';
    } else {
        $name = sanitize($_POST['name']);
    }
    
    // Validate email
    if (empty(trim($_POST['email']))) {
        $email_err = 'Please enter your email.';
    } else {
        $email = sanitize($_POST['email']);
        
        // Check if email is valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = 'Please enter a valid email address.';
        }
    }
    
    // Validate subject
    if (empty(trim($_POST['subject']))) {
        $subject_err = 'Please enter a subject.';
    } else {
        $subject = sanitize($_POST['subject']);
    }
    
    // Validate message
    if (empty(trim($_POST['message']))) {
        $message_err = 'Please enter your message.';
    } else {
        $message = sanitize($_POST['message']);
    }
    
    // If no errors, process the form
    if (empty($name_err) && empty($email_err) && empty($subject_err) && empty($message_err)) {
        // In a real application, you would send an email or save to database
        // For now, we'll just show a success message
        
        // Set success message
        $success_message = 'Your message has been sent successfully. We will get back to you soon.';
        
        // Clear form data
        $name = $email = $subject = $message = '';
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Support</h1>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <!-- Contact Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Contact Us</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>">
                            <span class="invalid-feedback"><?php echo $name_err; ?></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                            <span class="invalid-feedback"><?php echo $email_err; ?></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" name="subject" class="form-control <?php echo (!empty($subject_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $subject; ?>">
                            <span class="invalid-feedback"><?php echo $subject_err; ?></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea name="message" class="form-control <?php echo (!empty($message_err)) ? 'is-invalid' : ''; ?>" rows="5"><?php echo $message; ?></textarea>
                            <span class="invalid-feedback"><?php echo $message_err; ?></span>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="submit_contact" class="btn btn-primary">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <!-- FAQ -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Frequently Asked Questions</h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="faqAccordion">
                        <div class="card">
                            <div class="card-header" id="headingOne">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        How does P2P Donate work?
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#faqAccordion">
                                <div class="card-body">
                                    P2P Donate is a peer-to-peer donation platform that connects donors with recipients. Users can make pledges, which are then matched with recipients. The donor sends the payment directly to the recipient, who confirms receipt. The platform facilitates the matching and verification process.
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="headingTwo">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        How do I make a pledge?
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#faqAccordion">
                                <div class="card-body">
                                    To make a pledge, go to the Pledges page and click on "Make a Pledge". Enter the amount you wish to pledge and confirm. The system will deduct tokens from your balance and match you with a recipient when available.
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="headingThree">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        How do I buy tokens?
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#faqAccordion">
                                <div class="card-body">
                                    To buy tokens, go to the Wallet page. Enter the amount of tokens you wish to purchase, send the equivalent USDT to the provided wallet address, and upload proof of payment. Once verified by an administrator, the tokens will be credited to your account.
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="headingFour">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                        What if I have a dispute with another user?
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseFour" class="collapse" aria-labelledby="headingFour" data-parent="#faqAccordion">
                                <div class="card-body">
                                    If you have a dispute with another user, go to the Dispute Resolution page and submit a detailed report. Include any evidence such as screenshots or transaction IDs. An administrator will review the case and make a determination based on the evidence provided.
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="headingFive">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                        How long does it take to get matched?
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseFive" class="collapse" aria-labelledby="headingFive" data-parent="#faqAccordion">
                                <div class="card-body">
                                    Matching times vary depending on platform activity. In most cases, pledges are matched within 24-48 hours. You will receive a notification when you are matched with another user.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Response Time</h6>
                        <p>We typically respond to support inquiries within 24-48 hours. For urgent matters, please include "URGENT" in the subject line.</p>
                        
                        <h6>Contact Information</h6>
                        <p>Email: support@p2pdonate.com</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
