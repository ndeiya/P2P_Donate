<?php
// Set page title
$page_title = 'Legal';

// Include configuration
require_once 'config/config.php';

// Include header
require_once 'includes/header.php';

// Get requested page
$page = isset($_GET['page']) ? $_GET['page'] : 'terms';

// Set content based on page
$content_title = '';
$content = '';

switch ($page) {
    case 'terms':
        $content_title = 'Terms of Service';
        $content = '
        <p class="text-muted mb-4">Last Updated: ' . date('F d, Y') . '</p>

        <h4>1. Introduction</h4>
        <p>Welcome to ' . SITE_NAME . ' ("we," "our," or "us"). These Terms of Service ("Terms") govern your access to and use of the ' . SITE_NAME . ' platform, including any content, functionality, and services offered on or through our website (the "Service").</p>
        <p>By accessing or using the Service, you agree to be bound by these Terms. If you do not agree to these Terms, you must not access or use the Service.</p>

        <h4>2. Eligibility</h4>
        <p>You must be at least 18 years old to use the Service. By using the Service, you represent and warrant that you are at least 18 years old and have the legal capacity to enter into a binding agreement with us.</p>

        <h4>3. Account Registration</h4>
        <p>To access certain features of the Service, you may be required to register for an account. When you register, you agree to provide accurate, current, and complete information about yourself and to update such information as necessary to keep it accurate, current, and complete.</p>
        <p>You are responsible for safeguarding your account credentials and for all activities that occur under your account. You agree to notify us immediately of any unauthorized use of your account or any other breach of security.</p>

        <h4>4. Platform Tokens</h4>
        <p>Our Service uses a token system for platform operations. Tokens can be purchased using USDT at a fixed rate of 1 USDT = 10 tokens. The minimum token purchase is 5 USDT (50 tokens).</p>
        <p>Tokens are non-refundable and have no cash value outside of our platform. They can only be used for services within our platform, including making pledges and paying platform fees.</p>

        <h4>5. Pledge System</h4>
        <p>Our platform operates a pledge system where users can make pledges of a fixed amount (GHS ' . PLEDGE_AMOUNT . ') and receive pledges from other users. By participating in the pledge system, you acknowledge and agree to the following:</p>
        <ul>
            <li>Each pledge requires a platform fee of 10 tokens.</li>
            <li>After making a pledge, you will be placed in a queue to receive two consecutive pledges.</li>
            <li>After receiving two pledges, you will be removed from the queue.</li>
            <li>All payments between users happen offline and are not processed through our platform.</li>
            <li>We do not guarantee that you will receive pledges within any specific timeframe.</li>
            <li>We reserve the right to modify the pledge system, including the pledge amount and platform fee, at any time.</li>
        </ul>

        <h4>6. Payment Methods</h4>
        <p>Our platform accepts USDT payments on the following networks: Ethereum (ERC-20), Binance Smart Chain (BEP-20), Arbitrum, and Optimism. By making a payment, you acknowledge and agree to the following:</p>
        <ul>
            <li>You are responsible for ensuring that you send USDT on the correct network.</li>
            <li>We are not responsible for any loss of funds due to sending USDT on an incorrect or unsupported network.</li>
            <li>All transactions are final and non-refundable.</li>
            <li>We reserve the right to modify the supported payment networks at any time.</li>
        </ul>

        <h4>7. Prohibited Activities</h4>
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

        <h4>8. Intellectual Property Rights</h4>
        <p>The Service and its entire contents, features, and functionality (including but not limited to all information, software, text, displays, images, video, and audio, and the design, selection, and arrangement thereof) are owned by us, our licensors, or other providers of such material and are protected by copyright, trademark, patent, trade secret, and other intellectual property or proprietary rights laws.</p>

        <h4>9. Termination</h4>
        <p>We may terminate or suspend your account and bar access to the Service immediately, without prior notice or liability, under our sole discretion, for any reason whatsoever and without limitation, including but not limited to a breach of the Terms.</p>
        <p>If you wish to terminate your account, you may simply discontinue using the Service or contact us to request account deletion.</p>

        <h4>10. Limitation of Liability</h4>
        <p>In no event will we, our affiliates, or their licensors, service providers, employees, agents, officers, or directors be liable for damages of any kind, under any legal theory, arising out of or in connection with your use, or inability to use, the Service, including any direct, indirect, special, incidental, consequential, or punitive damages.</p>

        <h4>11. Disclaimer of Warranties</h4>
        <p>The Service is provided on an "AS IS" and "AS AVAILABLE" basis, without any warranties of any kind, either express or implied. We disclaim all warranties, including, but not limited to, implied warranties of merchantability, fitness for a particular purpose, and non-infringement.</p>

        <h4>12. Governing Law</h4>
        <p>These Terms shall be governed by and construed in accordance with the laws of Ghana, without regard to its conflict of law provisions.</p>

        <h4>13. Changes to Terms</h4>
        <p>We reserve the right to modify these Terms at any time. If we make changes to these Terms, we will provide notice of such changes, such as by sending an email notification, providing notice through the Service, or updating the "Last Updated" date at the beginning of these Terms.</p>

        <h4>14. Contact Information</h4>
        <p>If you have any questions about these Terms, please contact us at: contact@p2pdonate.com</p>
        ';
        break;

    case 'privacy':
        $content_title = 'Privacy Policy';
        $content = '
        <p class="text-muted mb-4">Last Updated: ' . date('F d, Y') . '</p>

        <h4>1. Introduction</h4>
        <p>Welcome to ' . SITE_NAME . ' ("we," "our," or "us"). We respect your privacy and are committed to protecting your personal data. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform.</p>
        <p>Please read this Privacy Policy carefully. If you do not agree with the terms of this Privacy Policy, please do not access or use our platform.</p>

        <h4>2. Information We Collect</h4>
        <p>We collect several types of information from and about users of our platform, including:</p>

        <h5>2.1 Personal Information</h5>
        <p>When you register for an account, we collect:</p>
        <ul>
            <li>Full name</li>
            <li>Email address</li>
            <li>Password (stored in encrypted form)</li>
            <li>Mobile money number</li>
            <li>Mobile money account name</li>
        </ul>

        <h5>2.2 Transaction Information</h5>
        <p>When you use our platform, we collect information about your transactions, including:</p>
        <ul>
            <li>Token purchases and usage</li>
            <li>Pledges made and received</li>
            <li>Transaction references</li>
            <li>Payment proofs uploaded</li>
            <li>Cryptocurrency wallet addresses used for payments</li>
            <li>Blockchain networks used for transactions</li>
        </ul>

        <h5>2.3 Technical Information</h5>
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

        <h4>3. How We Use Your Information</h4>
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

        <h4>4. How We Share Your Information</h4>
        <p>We may share your information in the following situations:</p>

        <h5>4.1 With Other Users</h5>
        <p>When you make or receive a pledge, we share limited information with the other user involved in the transaction, including:</p>
        <ul>
            <li>Your name</li>
            <li>Your mobile money number</li>
            <li>Your mobile money account name</li>
        </ul>
        <p>This information is necessary for users to complete the offline payment process.</p>

        <h5>4.2 With Service Providers</h5>
        <p>We may share your information with third-party vendors, service providers, contractors, or agents who perform services for us or on our behalf and require access to such information to do that work.</p>

        <h5>4.3 For Legal Purposes</h5>
        <p>We may disclose your information where required to do so by law or in response to valid requests by public authorities (e.g., a court or a government agency).</p>

        <h5>4.4 Business Transfers</h5>
        <p>We may share or transfer your information in connection with, or during negotiations of, any merger, sale of company assets, financing, or acquisition of all or a portion of our business to another company.</p>

        <h4>5. Data Security</h4>
        <p>We have implemented appropriate technical and organizational security measures designed to protect the security of any personal information we process. However, despite our safeguards and efforts to secure your information, no electronic transmission over the Internet or information storage technology can be guaranteed to be 100% secure.</p>

        <h4>6. Data Retention</h4>
        <p>We will only keep your personal information for as long as it is necessary for the purposes set out in this Privacy Policy, unless a longer retention period is required or permitted by law.</p>

        <h4>7. Your Privacy Rights</h4>
        <p>Depending on your location, you may have certain rights regarding your personal information, including:</p>
        <ul>
            <li>The right to access the personal information we have about you</li>
            <li>The right to request that we correct any inaccurate personal information we have about you</li>
            <li>The right to request that we delete any personal information we have about you</li>
            <li>The right to opt-out of marketing communications</li>
        </ul>
        <p>To exercise any of these rights, please contact us using the contact information provided below.</p>

        <h4>8. Third-Party Websites</h4>
        <p>Our platform may contain links to third-party websites and applications. We are not responsible for the privacy practices or the content of these third-party sites. We encourage you to read the privacy policy of every website you visit.</p>

        <h4>9. Children\'s Privacy</h4>
        <p>Our platform is not intended for individuals under the age of 18. We do not knowingly collect personal information from children under 18. If you are a parent or guardian and you are aware that your child has provided us with personal information, please contact us so that we can take necessary actions.</p>

        <h4>10. Changes to This Privacy Policy</h4>
        <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last Updated" date at the top of this Privacy Policy.</p>
        <p>You are advised to review this Privacy Policy periodically for any changes. Changes to this Privacy Policy are effective when they are posted on this page.</p>

        <h4>11. Contact Information</h4>
        <p>If you have any questions about this Privacy Policy, please contact us at: contact@p2pdonate.com</p>
        ';
        break;

    case 'disclaimer':
        $content_title = 'Disclaimer';
        $content = '
        <p class="text-muted mb-4">Last Updated: ' . date('F d, Y') . '</p>

        <h4>Platform Disclaimer</h4>
        <p>Please read this disclaimer carefully before using the ' . SITE_NAME . ' platform.</p>

        <div class="alert alert-warning">
            <h5>Risk Acknowledgment</h5>
            <p>' . SITE_NAME . ' is a peer-to-peer donation platform that facilitates connections between users who wish to make and receive pledges. By using our platform, you acknowledge and accept the following risks:</p>
            <ul>
                <li><strong>No Guarantees:</strong> We do not guarantee that you will receive pledges within any specific timeframe or at all. The pledge system depends on the participation and honesty of other users.</li>
                <li><strong>Peer-to-Peer Transactions:</strong> All payments between users happen offline and are not processed through our platform. We do not hold or transfer funds between users.</li>
                <li><strong>User Verification Limitations:</strong> While we take measures to verify user identities, we cannot guarantee the authenticity or reliability of all users on the platform.</li>
                <li><strong>Financial Risk:</strong> Participation in the pledge system involves financial risk. You may not receive pledges equal to or greater than the pledges you make.</li>
                <li><strong>Cryptocurrency Volatility:</strong> The value of cryptocurrencies, including USDT, can be volatile. We are not responsible for any loss of value due to cryptocurrency price fluctuations.</li>
            </ul>
        </div>

        <h4>Not Financial Advice</h4>
        <p>The information provided on our platform is for general informational purposes only and should not be construed as financial, investment, or legal advice. We are not financial advisors, and we do not provide personalized financial advice.</p>
        <p>Before making any financial decisions, including purchasing tokens or making pledges, you should consult with a qualified financial advisor to determine what may be best for your individual needs.</p>

        <h4>No Pyramid or Ponzi Scheme</h4>
        <p>' . SITE_NAME . ' is a peer-to-peer donation platform and is not a pyramid scheme, Ponzi scheme, or investment program. Users participate voluntarily in a community-based donation system where they make pledges to others and may receive pledges in return.</p>
        <p>Our platform does not promise or guarantee returns on investments, and we do not use new users\' funds to pay existing users. All transactions are direct donations between users.</p>

        <h4>Platform Fees</h4>
        <p>We charge a platform fee of 10 tokens for each pledge made. These fees are used to maintain and improve the platform, provide customer support, and cover operational costs. Platform fees are non-refundable.</p>

        <h4>Cryptocurrency Payments</h4>
        <p>Our platform accepts USDT payments on Ethereum, BSC, Arbitrum, and Optimism networks. By making a payment, you acknowledge the following:</p>
        <ul>
            <li>You are responsible for ensuring that you send USDT on the correct network.</li>
            <li>We are not responsible for any loss of funds due to sending USDT on an incorrect or unsupported network.</li>
            <li>All transactions are final and non-refundable.</li>
            <li>You are responsible for complying with all applicable laws and regulations regarding cryptocurrency transactions in your jurisdiction.</li>
        </ul>

        <h4>Limitation of Liability</h4>
        <p>To the maximum extent permitted by law, ' . SITE_NAME . ', its owners, operators, affiliates, and licensors shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from:</p>
        <ul>
            <li>Your access to or use of or inability to access or use the platform;</li>
            <li>Any conduct or content of any third party on the platform;</li>
            <li>Any content obtained from the platform; and</li>
            <li>Unauthorized access, use, or alteration of your transmissions or content.</li>
        </ul>

        <h4>Legal Compliance</h4>
        <p>You are responsible for ensuring that your use of our platform complies with all applicable laws, regulations, and rules in your jurisdiction. We do not represent or warrant that our platform is appropriate or available for use in any particular jurisdiction.</p>

        <h4>Changes to This Disclaimer</h4>
        <p>We reserve the right to modify this disclaimer at any time. We will notify users of any changes by posting the new disclaimer on this page and updating the "Last Updated" date.</p>
        <p>Your continued use of the platform after any such changes constitutes your acceptance of the new disclaimer.</p>

        <h4>Contact Information</h4>
        <p>If you have any questions about this disclaimer, please contact us at: contact@p2pdonate.com</p>
        ';
        break;

    default:
        $content_title = 'Terms of Use';
        $content = '
        <h4>1. Acceptance of Terms</h4>
        <p>By accessing or using the P2P Donate platform, you agree to be bound by these Terms of Use. If you do not agree to these terms, please do not use the platform.</p>

        <h4>2. Description of Service</h4>
        <p>P2P Donate is a peer-to-peer donation platform that facilitates direct transfers between users. The platform matches donors with recipients and provides a system for verifying transfers.</p>

        <h4>3. User Accounts</h4>
        <p>To use P2P Donate, you must create an account with accurate and complete information. You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>

        <h4>4. User Conduct</h4>
        <p>You agree not to use the platform for any illegal or unauthorized purpose. You must not attempt to manipulate the platform, interfere with other users, or engage in fraudulent activities.</p>

        <h4>5. Pledges and Matches</h4>
        <p>When you make a pledge, you commit to sending the specified amount to the matched recipient. Failure to fulfill a pledge may result in account restrictions or termination.</p>

        <h4>6. Payments</h4>
        <p>P2P Donate does not process payments directly. All transfers occur between users through their own payment methods. The platform only facilitates the matching and verification process.</p>

        <h4>7. Dispute Resolution</h4>
        <p>In case of disputes between users, P2P Donate provides a resolution system. The platform administrators will review evidence from both parties and make a determination based on the available information.</p>

        <h4>8. Limitation of Liability</h4>
        <p>P2P Donate is not responsible for any losses, damages, or disputes arising from the use of the platform. Users engage in transfers at their own risk.</p>

        <h4>9. Termination</h4>
        <p>P2P Donate reserves the right to terminate or suspend accounts that violate these terms or engage in suspicious activities.</p>

        <h4>10. Changes to Terms</h4>
        <p>P2P Donate may modify these terms at any time. Continued use of the platform after changes constitutes acceptance of the new terms.</p>
        ';
        break;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Legal</h1>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="list-group mb-4">
                <a href="legal.php?page=terms" class="list-group-item list-group-item-action <?php echo ($page == 'terms') ? 'active' : ''; ?>">Terms of Use</a>
                <a href="legal.php?page=privacy" class="list-group-item list-group-item-action <?php echo ($page == 'privacy') ? 'active' : ''; ?>">Privacy Policy</a>
                <a href="legal.php?page=disclaimer" class="list-group-item list-group-item-action <?php echo ($page == 'disclaimer') ? 'active' : ''; ?>">Disclaimer</a>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo $content_title; ?></h5>
                </div>
                <div class="card-body">
                    <?php echo $content; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
