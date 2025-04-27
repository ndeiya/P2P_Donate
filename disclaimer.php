<?php
// Set page title
$page_title = 'Disclaimer';

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
                    <h1 class="h3 mb-0">Disclaimer</h1>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">Last Updated: <?php echo date('F d, Y'); ?></p>
                    
                    <h2 class="h4">Platform Disclaimer</h2>
                    <p>Please read this disclaimer carefully before using the <?php echo SITE_NAME; ?> platform.</p>
                    
                    <div class="alert alert-warning">
                        <h3 class="h5">Risk Acknowledgment</h3>
                        <p><?php echo SITE_NAME; ?> is a peer-to-peer donation platform that facilitates connections between users who wish to make and receive pledges. By using our platform, you acknowledge and accept the following risks:</p>
                        <ul>
                            <li><strong>No Guarantees:</strong> We do not guarantee that you will receive pledges within any specific timeframe or at all. The pledge system depends on the participation and honesty of other users.</li>
                            <li><strong>Peer-to-Peer Transactions:</strong> All payments between users happen offline and are not processed through our platform. We do not hold or transfer funds between users.</li>
                            <li><strong>User Verification Limitations:</strong> While we take measures to verify user identities, we cannot guarantee the authenticity or reliability of all users on the platform.</li>
                            <li><strong>Financial Risk:</strong> Participation in the pledge system involves financial risk. You may not receive pledges equal to or greater than the pledges you make.</li>
                            <li><strong>Cryptocurrency Volatility:</strong> The value of cryptocurrencies, including USDT, can be volatile. We are not responsible for any loss of value due to cryptocurrency price fluctuations.</li>
                        </ul>
                    </div>
                    
                    <h2 class="h4">Not Financial Advice</h2>
                    <p>The information provided on our platform is for general informational purposes only and should not be construed as financial, investment, or legal advice. We are not financial advisors, and we do not provide personalized financial advice.</p>
                    <p>Before making any financial decisions, including purchasing tokens or making pledges, you should consult with a qualified financial advisor to determine what may be best for your individual needs.</p>
                    
                    <h2 class="h4">No Pyramid or Ponzi Scheme</h2>
                    <p><?php echo SITE_NAME; ?> is a peer-to-peer donation platform and is not a pyramid scheme, Ponzi scheme, or investment program. Users participate voluntarily in a community-based donation system where they make pledges to others and may receive pledges in return.</p>
                    <p>Our platform does not promise or guarantee returns on investments, and we do not use new users' funds to pay existing users. All transactions are direct donations between users.</p>
                    
                    <h2 class="h4">Platform Fees</h2>
                    <p>We charge a platform fee of 10 tokens for each pledge made. These fees are used to maintain and improve the platform, provide customer support, and cover operational costs. Platform fees are non-refundable.</p>
                    
                    <h2 class="h4">Cryptocurrency Payments</h2>
                    <p>Our platform accepts USDT payments on Ethereum, BSC, Arbitrum, and Optimism networks. By making a payment, you acknowledge the following:</p>
                    <ul>
                        <li>You are responsible for ensuring that you send USDT on the correct network.</li>
                        <li>We are not responsible for any loss of funds due to sending USDT on an incorrect or unsupported network.</li>
                        <li>All transactions are final and non-refundable.</li>
                        <li>You are responsible for complying with all applicable laws and regulations regarding cryptocurrency transactions in your jurisdiction.</li>
                    </ul>
                    
                    <h2 class="h4">Limitation of Liability</h2>
                    <p>To the maximum extent permitted by law, <?php echo SITE_NAME; ?>, its owners, operators, affiliates, and licensors shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from:</p>
                    <ul>
                        <li>Your access to or use of or inability to access or use the platform;</li>
                        <li>Any conduct or content of any third party on the platform;</li>
                        <li>Any content obtained from the platform; and</li>
                        <li>Unauthorized access, use, or alteration of your transmissions or content.</li>
                    </ul>
                    
                    <h2 class="h4">Legal Compliance</h2>
                    <p>You are responsible for ensuring that your use of our platform complies with all applicable laws, regulations, and rules in your jurisdiction. We do not represent or warrant that our platform is appropriate or available for use in any particular jurisdiction.</p>
                    
                    <h2 class="h4">Changes to This Disclaimer</h2>
                    <p>We reserve the right to modify this disclaimer at any time. We will notify users of any changes by posting the new disclaimer on this page and updating the "Last Updated" date.</p>
                    <p>Your continued use of the platform after any such changes constitutes your acceptance of the new disclaimer.</p>
                    
                    <h2 class="h4">Contact Information</h2>
                    <p>If you have any questions about this disclaimer, please contact us at:</p>
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
