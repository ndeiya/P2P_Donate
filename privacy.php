<?php
// Set page title
$page_title = 'Privacy Policy';

// Include configuration
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'database/db_connect.php';

// Start session
start_session();

// Include header
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h1 class="h3 mb-0">Privacy Policy</h1>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">Last Updated: <?php echo date('F d, Y'); ?></p>
                    
                    <h2 class="h4">1. Introduction</h2>
                    <p>Welcome to <?php echo SITE_NAME; ?> ("we," "our," or "us"). We respect your privacy and are committed to protecting your personal data. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform.</p>
                    <p>Please read this Privacy Policy carefully. If you do not agree with the terms of this Privacy Policy, please do not access or use our platform.</p>
                    
                    <h2 class="h4">2. Information We Collect</h2>
                    <p>We collect several types of information from and about users of our platform, including:</p>
                    
                    <h3 class="h5">2.1 Personal Information</h3>
                    <p>When you register for an account, we collect:</p>
                    <ul>
                        <li>Full name</li>
                        <li>Email address</li>
                        <li>Password (stored in encrypted form)</li>
                        <li>Mobile money number</li>
                        <li>Mobile money account name</li>
                    </ul>
                    
                    <h3 class="h5">2.2 Transaction Information</h3>
                    <p>When you use our platform, we collect information about your transactions, including:</p>
                    <ul>
                        <li>Token purchases and usage</li>
                        <li>Pledges made and received</li>
                        <li>Transaction references</li>
                        <li>Payment proofs uploaded</li>
                        <li>Cryptocurrency wallet addresses used for payments</li>
                        <li>Blockchain networks used for transactions</li>
                    </ul>
                    
                    <h3 class="h5">2.3 Technical Information</h3>
                    <p>We automatically collect certain information when you visit, use, or navigate our platform, including:</p>
                    <ul>
                        <li>IP address</li>
                        <li>Browser type and version</li>
                        <li>Device type</li>
                        <li>Operating system</li>
                        <li>Access times and dates</li>
                        <li>Pages viewed</li>
                        <li>Referring website addresses</li>
                    </ul>
                    
                    <h2 class="h4">3. How We Use Your Information</h2>
                    <p>We use the information we collect for various purposes, including:</p>
                    <ul>
                        <li>To create and maintain your account</li>
                        <li>To process and manage your transactions</li>
                        <li>To match users for pledges</li>
                        <li>To communicate with you about your account and transactions</li>
                        <li>To provide customer support</li>
                        <li>To enforce our terms, conditions, and policies</li>
                        <li>To detect, prevent, and address technical issues</li>
                        <li>To improve our platform and user experience</li>
                        <li>To comply with legal obligations</li>
                    </ul>
                    
                    <h2 class="h4">4. How We Share Your Information</h2>
                    <p>We may share your information in the following situations:</p>
                    
                    <h3 class="h5">4.1 With Other Users</h3>
                    <p>When you make or receive a pledge, we share limited information with the other user involved in the transaction, including:</p>
                    <ul>
                        <li>Your name</li>
                        <li>Your mobile money number</li>
                        <li>Your mobile money account name</li>
                    </ul>
                    <p>This information is necessary for users to complete the offline payment process.</p>
                    
                    <h3 class="h5">4.2 With Service Providers</h3>
                    <p>We may share your information with third-party vendors, service providers, contractors, or agents who perform services for us or on our behalf and require access to such information to do that work.</p>
                    
                    <h3 class="h5">4.3 For Legal Purposes</h3>
                    <p>We may disclose your information where required to do so by law or in response to valid requests by public authorities (e.g., a court or a government agency).</p>
                    
                    <h3 class="h5">4.4 Business Transfers</h3>
                    <p>We may share or transfer your information in connection with, or during negotiations of, any merger, sale of company assets, financing, or acquisition of all or a portion of our business to another company.</p>
                    
                    <h2 class="h4">5. Data Security</h2>
                    <p>We have implemented appropriate technical and organizational security measures designed to protect the security of any personal information we process. However, despite our safeguards and efforts to secure your information, no electronic transmission over the Internet or information storage technology can be guaranteed to be 100% secure.</p>
                    
                    <h2 class="h4">6. Data Retention</h2>
                    <p>We will only keep your personal information for as long as it is necessary for the purposes set out in this Privacy Policy, unless a longer retention period is required or permitted by law.</p>
                    
                    <h2 class="h4">7. Your Privacy Rights</h2>
                    <p>Depending on your location, you may have certain rights regarding your personal information, including:</p>
                    <ul>
                        <li>The right to access the personal information we have about you</li>
                        <li>The right to request that we correct any inaccurate personal information we have about you</li>
                        <li>The right to request that we delete any personal information we have about you</li>
                        <li>The right to opt-out of marketing communications</li>
                    </ul>
                    <p>To exercise any of these rights, please contact us using the contact information provided below.</p>
                    
                    <h2 class="h4">8. Third-Party Websites</h2>
                    <p>Our platform may contain links to third-party websites and applications. We are not responsible for the privacy practices or the content of these third-party sites. We encourage you to read the privacy policy of every website you visit.</p>
                    
                    <h2 class="h4">9. Children's Privacy</h2>
                    <p>Our platform is not intended for individuals under the age of 18. We do not knowingly collect personal information from children under 18. If you are a parent or guardian and you are aware that your child has provided us with personal information, please contact us so that we can take necessary actions.</p>
                    
                    <h2 class="h4">10. Changes to This Privacy Policy</h2>
                    <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last Updated" date at the top of this Privacy Policy.</p>
                    <p>You are advised to review this Privacy Policy periodically for any changes. Changes to this Privacy Policy are effective when they are posted on this page.</p>
                    
                    <h2 class="h4">11. Contact Information</h2>
                    <p>If you have any questions about this Privacy Policy, please contact us at:</p>
                    <p>Email: [Your Contact Email]</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
