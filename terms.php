<?php
// Set page title
$page_title = 'Terms of Service';

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
                    <h1 class="h3 mb-0">Terms of Service</h1>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">Last Updated: <?php echo date('F d, Y'); ?></p>
                    
                    <h2 class="h4">1. Introduction</h2>
                    <p>Welcome to <?php echo SITE_NAME; ?> ("we," "our," or "us"). These Terms of Service ("Terms") govern your access to and use of the <?php echo SITE_NAME; ?> platform, including any content, functionality, and services offered on or through our website (the "Service").</p>
                    <p>By accessing or using the Service, you agree to be bound by these Terms. If you do not agree to these Terms, you must not access or use the Service.</p>
                    
                    <h2 class="h4">2. Eligibility</h2>
                    <p>You must be at least 18 years old to use the Service. By using the Service, you represent and warrant that you are at least 18 years old and have the legal capacity to enter into a binding agreement with us.</p>
                    
                    <h2 class="h4">3. Account Registration</h2>
                    <p>To access certain features of the Service, you may be required to register for an account. When you register, you agree to provide accurate, current, and complete information about yourself and to update such information as necessary to keep it accurate, current, and complete.</p>
                    <p>You are responsible for safeguarding your account credentials and for all activities that occur under your account. You agree to notify us immediately of any unauthorized use of your account or any other breach of security.</p>
                    
                    <h2 class="h4">4. Platform Tokens</h2>
                    <p>Our Service uses a token system for platform operations. Tokens can be purchased using USDT at a fixed rate of 1 USDT = 10 tokens. The minimum token purchase is 5 USDT (50 tokens).</p>
                    <p>Tokens are non-refundable and have no cash value outside of our platform. They can only be used for services within our platform, including making pledges and paying platform fees.</p>
                    
                    <h2 class="h4">5. Pledge System</h2>
                    <p>Our platform operates a pledge system where users can make pledges of a fixed amount (GHS <?php echo PLEDGE_AMOUNT; ?>) and receive pledges from other users. By participating in the pledge system, you acknowledge and agree to the following:</p>
                    <ul>
                        <li>Each pledge requires a platform fee of 10 tokens.</li>
                        <li>After making a pledge, you will be placed in a queue to receive two consecutive pledges.</li>
                        <li>After receiving two pledges, you will be removed from the queue.</li>
                        <li>All payments between users happen offline and are not processed through our platform.</li>
                        <li>We do not guarantee that you will receive pledges within any specific timeframe.</li>
                        <li>We reserve the right to modify the pledge system, including the pledge amount and platform fee, at any time.</li>
                    </ul>
                    
                    <h2 class="h4">6. Payment Methods</h2>
                    <p>Our platform accepts USDT payments on the following networks: Ethereum (ERC-20), Binance Smart Chain (BEP-20), Arbitrum, and Optimism. By making a payment, you acknowledge and agree to the following:</p>
                    <ul>
                        <li>You are responsible for ensuring that you send USDT on the correct network.</li>
                        <li>We are not responsible for any loss of funds due to sending USDT on an incorrect or unsupported network.</li>
                        <li>All transactions are final and non-refundable.</li>
                        <li>We reserve the right to modify the supported payment networks at any time.</li>
                    </ul>
                    
                    <h2 class="h4">7. Prohibited Activities</h2>
                    <p>You agree not to engage in any of the following prohibited activities:</p>
                    <ul>
                        <li>Using the Service for any illegal purpose or in violation of any local, state, national, or international law.</li>
                        <li>Creating multiple accounts to abuse the pledge system.</li>
                        <li>Providing false or misleading information when registering for an account or making a pledge.</li>
                        <li>Attempting to circumvent any security measures or features of the Service.</li>
                        <li>Engaging in any activity that disrupts, damages, or interferes with the Service.</li>
                        <li>Using the Service to send unsolicited communications, promotions, or advertisements.</li>
                        <li>Impersonating another person or entity or falsely stating or otherwise misrepresenting your affiliation with a person or entity.</li>
                    </ul>
                    
                    <h2 class="h4">8. Intellectual Property Rights</h2>
                    <p>The Service and its entire contents, features, and functionality (including but not limited to all information, software, text, displays, images, video, and audio, and the design, selection, and arrangement thereof) are owned by us, our licensors, or other providers of such material and are protected by copyright, trademark, patent, trade secret, and other intellectual property or proprietary rights laws.</p>
                    <p>You may not reproduce, distribute, modify, create derivative works of, publicly display, publicly perform, republish, download, store, or transmit any of the material on our Service, except as follows:</p>
                    <ul>
                        <li>Your computer may temporarily store copies of such materials in RAM incidental to your accessing and viewing those materials.</li>
                        <li>You may store files that are automatically cached by your Web browser for display enhancement purposes.</li>
                        <li>You may print or download one copy of a reasonable number of pages of the Service for your own personal, non-commercial use and not for further reproduction, publication, or distribution.</li>
                    </ul>
                    
                    <h2 class="h4">9. Termination</h2>
                    <p>We may terminate or suspend your account and bar access to the Service immediately, without prior notice or liability, under our sole discretion, for any reason whatsoever and without limitation, including but not limited to a breach of the Terms.</p>
                    <p>If you wish to terminate your account, you may simply discontinue using the Service or contact us to request account deletion.</p>
                    
                    <h2 class="h4">10. Limitation of Liability</h2>
                    <p>In no event will we, our affiliates, or their licensors, service providers, employees, agents, officers, or directors be liable for damages of any kind, under any legal theory, arising out of or in connection with your use, or inability to use, the Service, including any direct, indirect, special, incidental, consequential, or punitive damages, including but not limited to, personal injury, pain and suffering, emotional distress, loss of revenue, loss of profits, loss of business or anticipated savings, loss of use, loss of goodwill, loss of data, and whether caused by tort (including negligence), breach of contract, or otherwise, even if foreseeable.</p>
                    
                    <h2 class="h4">11. Disclaimer of Warranties</h2>
                    <p>The Service is provided on an "AS IS" and "AS AVAILABLE" basis, without any warranties of any kind, either express or implied. We disclaim all warranties, including, but not limited to, implied warranties of merchantability, fitness for a particular purpose, and non-infringement.</p>
                    <p>We do not warrant that the Service will be uninterrupted or error-free, that defects will be corrected, or that the Service or the server that makes it available are free of viruses or other harmful components.</p>
                    
                    <h2 class="h4">12. Governing Law</h2>
                    <p>These Terms shall be governed by and construed in accordance with the laws of Ghana, without regard to its conflict of law provisions.</p>
                    
                    <h2 class="h4">13. Changes to Terms</h2>
                    <p>We reserve the right to modify these Terms at any time. If we make changes to these Terms, we will provide notice of such changes, such as by sending an email notification, providing notice through the Service, or updating the "Last Updated" date at the beginning of these Terms.</p>
                    <p>By continuing to access or use the Service after the revisions become effective, you agree to be bound by the revised Terms. If you do not agree to the new Terms, you are no longer authorized to use the Service.</p>
                    
                    <h2 class="h4">14. Contact Information</h2>
                    <p>If you have any questions about these Terms, please contact us at:</p>
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
