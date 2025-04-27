<?php
// List of admin PHP files
$admin_files = [
    'admin/add_pledge.php',
    'admin/disputes.php',
    'admin/index.php',
    'admin/pledges.php',
    'admin/pledge_queue.php',
    'admin/referrals.php',
    'admin/settings.php',
    'admin/test_dispute.php',
    'admin/tokens.php',
    'admin/transactions.php',
    'admin/users.php',
    'admin/user_details.php'
];

// Pattern to search for
$search_pattern = '<link rel="stylesheet" href="../assets/css/admin.css">';

// Replacement with dark mode CSS
$replacement = '<link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-dark-mode.css">';

// Process each file
foreach ($admin_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check if the dark mode CSS is already included
        if (strpos($content, 'admin-dark-mode.css') === false) {
            // Replace the pattern
            $new_content = str_replace($search_pattern, $replacement, $content);
            
            // Write the updated content back to the file
            if ($content !== $new_content) {
                file_put_contents($file, $new_content);
                echo "Updated: $file\n";
            } else {
                echo "No changes needed for: $file\n";
            }
        } else {
            echo "Dark mode CSS already included in: $file\n";
        }
    } else {
        echo "File not found: $file\n";
    }
}

echo "Done!\n";
?>
