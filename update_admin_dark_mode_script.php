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
$search_pattern = '<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>';

// Replacement with dark mode script
$replacement = '<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <?php include \'includes/dark_mode_script.php\'; ?>';

// Process each file
foreach ($admin_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check if the dark mode script is already included
        if (strpos($content, 'dark_mode_script.php') === false) {
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
            echo "Dark mode script already included in: $file\n";
        }
    } else {
        echo "File not found: $file\n";
    }
}

echo "Done!\n";
?>
