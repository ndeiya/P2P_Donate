<?php
// Include configuration
require_once 'config/config.php';
require_once 'includes/functions.php';

// Start session
start_session();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
redirect('login.php');
?>
