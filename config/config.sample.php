<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_NAME', 'p2p_donate');

// Application Configuration
define('SITE_NAME', 'P2P Donate');
define('SITE_URL', 'https://your-domain.com');

// Token Configuration
define('TOKEN_RATE', 0.1); // 1 USDT = 10 Token

// Pledge Configuration
define('PLEDGE_AMOUNT', '200'); // Fixed pledge amount in GHS

// Session Configuration
define('SESSION_NAME', 'p2p_donate_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// File Upload Configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
?>
