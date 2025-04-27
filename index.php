<?php
// Define the root path to make includes work from any directory
define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);

// Include configuration
require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// Start session
start_session();

// Check if user is logged in
if (is_logged_in()) {
    // Redirect to dashboard
    redirect('dashboard.php');
} else {
    // Redirect to login page
    redirect('login.php');
}
?>
