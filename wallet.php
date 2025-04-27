<?php
// Set page title
$page_title = 'Wallet';

// Include header
require_once 'includes/header.php';

// Include wallet configuration
require_once 'config/wallet.php';

// Get user token balance
$token_balance = get_token_balance($user_id, $db);

// Process token purchase form
$amount = $reference = $network = '';
$amount_err = $reference_err = $network_err = $file_err = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['buy_tokens'])) {
    // Validate amount
    if (empty(trim($_POST['amount']))) {
        $amount_err = 'Please enter an amount.';
    } elseif (!is_numeric($_POST['amount']) || $_POST['amount'] <= 0) {
        $amount_err = 'Please enter a valid amount.';
    } elseif ($_POST['amount'] < 50) {
        $amount_err = 'Minimum purchase is 50 tokens (5 USDT).';
    } else {
        $amount = floatval($_POST['amount']);
    }

    // Validate network
    if (empty(trim($_POST['network']))) {
        $network_err = 'Please select a network.';
    } elseif (!array_key_exists($_POST['network'], $wallet_networks)) {
        $network_err = 'Please select a valid network.';
    } else {
        $network = sanitize($_POST['network']);
    }

    // Validate reference (transaction hash)
    if (empty(trim($_POST['reference']))) {
        $reference_err = 'Please enter a transaction hash.';
    } else {
        $reference = sanitize($_POST['reference']);

        // Ensure the reference starts with 0x
        if (strpos($reference, '0x') !== 0) {
            $reference = '0x' . $reference;
        }

        // Validate transaction hash format (0x followed by 64 hexadecimal characters)
        if (!preg_match('/^0x[a-fA-F0-9]{64}$/', $reference)) {
            $reference_err = 'Please enter a valid transaction hash (0x followed by 64 hexadecimal characters).';
        }
    }

    // Validate file upload (optional)
    $proof_file_name = null;
    if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] != UPLOAD_ERR_NO_FILE) {
        $upload_result = upload_file($_FILES['proof_file'], 'uploads/proofs/');

        if (!$upload_result['success']) {
            $file_err = $upload_result['message'];
        } else {
            $proof_file_name = $upload_result['filename'];
        }
    }

    // If no errors, process the purchase
    if (empty($amount_err) && empty($reference_err) && empty($network_err) && empty($file_err)) {
        // Create reference with network info
        $full_reference = $network . ': ' . $reference;

        // Check if the notes column exists in the tokens table
        $query = "SHOW COLUMNS FROM tokens LIKE 'notes'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $notes_column_exists = $stmt->rowCount() > 0;

        if ($notes_column_exists) {
            // Insert token purchase record with notes
            if ($proof_file_name) {
                // With proof file
                $query = "INSERT INTO tokens (user_id, amount, transaction_type, reference, proof_file, notes) VALUES (:user_id, :amount, 'purchase', :reference, :proof_file, :notes)";
                $stmt = $db->prepare($query);

                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':reference', $full_reference);
                $stmt->bindParam(':proof_file', $proof_file_name);
                $stmt->bindParam(':notes', $network);
            } else {
                // Without proof file
                $query = "INSERT INTO tokens (user_id, amount, transaction_type, reference, notes) VALUES (:user_id, :amount, 'purchase', :reference, :notes)";
                $stmt = $db->prepare($query);

                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':reference', $full_reference);
                $stmt->bindParam(':notes', $network);
            }
        } else {
            // Insert token purchase record without notes
            if ($proof_file_name) {
                // With proof file
                $query = "INSERT INTO tokens (user_id, amount, transaction_type, reference, proof_file) VALUES (:user_id, :amount, 'purchase', :reference, :proof_file)";
                $stmt = $db->prepare($query);

                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':reference', $full_reference);
                $stmt->bindParam(':proof_file', $proof_file_name);
            } else {
                // Without proof file
                $query = "INSERT INTO tokens (user_id, amount, transaction_type, reference) VALUES (:user_id, :amount, 'purchase', :reference)";
                $stmt = $db->prepare($query);

                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':reference', $full_reference);
            }
        }

        if ($stmt->execute()) {
            // Create notification
            create_notification($user_id, 'Token Purchase Pending', 'Your token purchase of ' . format_currency($amount, 'Tokens') . ' is pending approval.', 'token', $db);

            // Set success message
            $success_message = 'Your token purchase request has been submitted and is pending approval.';

            // Clear form data
            $amount = $reference = '';
        } else {
            $error_message = 'Something went wrong. Please try again.';
        }
    }
}

// Get transaction history
$query = "SELECT * FROM tokens WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Wallet</h1>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <!-- Wallet Summary -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Wallet Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="text-center mb-3">
                                <h6>Token Balance</h6>
                                <h2><?php echo format_currency($token_balance, 'Tokens'); ?></h2>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center mb-3">
                                <h6>USDT Info</h6>
                                <h2>1 USDT = 10 Tokens</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Buy Tokens Block -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Buy Tokens</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="amount">Amount (Tokens)</label>
                            <input type="number" name="amount" id="token-amount" class="form-control <?php echo (!empty($amount_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $amount; ?>" min="50" step="1">
                            <span class="invalid-feedback"><?php echo $amount_err; ?></span>
                            <small class="form-text text-muted">Minimum purchase: 50 tokens (5 USDT)</small>
                        </div>

                        <div class="form-group">
                            <label for="usdt-amount">USDT Amount</label>
                            <input type="text" id="usdt-amount" class="form-control" readonly>
                            <input type="hidden" id="token-rate" value="<?php echo TOKEN_RATE; ?>">
                        </div>

                        <div class="alert alert-info payment-instructions">
                            <h6>Payment Instructions:</h6>
                            <p>Please send the USDT amount to our wallet address using one of the following networks:</p>

                            <div class="card mb-3">
                                <div class="card-header p-2">
                                    <ul class="nav nav-tabs card-header-tabs" id="networkTabs" role="tablist">
                                        <?php $first = true; foreach ($wallet_networks as $network_key => $network): ?>
                                            <li class="nav-item">
                                                <a class="nav-link <?php echo $first ? 'active' : ''; ?>"
                                                   id="<?php echo strtolower($network_key); ?>-tab"
                                                   data-toggle="tab"
                                                   href="#<?php echo strtolower($network_key); ?>-content"
                                                   role="tab"
                                                   aria-controls="<?php echo strtolower($network_key); ?>-content"
                                                   aria-selected="<?php echo $first ? 'true' : 'false'; ?>">
                                                    <i class="<?php echo $network['icon']; ?> mr-1"></i> <?php echo $network_key; ?>
                                                </a>
                                            </li>
                                        <?php $first = false; endforeach; ?>
                                    </ul>
                                </div>
                                <div class="card-body p-3">
                                    <div class="tab-content" id="networkTabsContent">
                                        <?php $first = true; foreach ($wallet_networks as $network_key => $network): ?>
                                            <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>"
                                                 id="<?php echo strtolower($network_key); ?>-content"
                                                 role="tabpanel"
                                                 aria-labelledby="<?php echo strtolower($network_key); ?>-tab">

                                                <h6 class="mb-2"><?php echo $network['name']; ?></h6>
                                                <div class="input-group mb-2">
                                                    <input type="text" class="form-control" value="<?php echo $network['address']; ?>" readonly>
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard(this)">
                                                            <i class="fas fa-copy"></i> Copy
                                                        </button>
                                                    </div>
                                                </div>
                                                <small class="text-muted">Make sure to send USDT on the <?php echo $network['name']; ?> network only.</small>
                                            </div>
                                        <?php $first = false; endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-warning mb-3 payment-instructions">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Important:</strong> Send USDT only on the networks listed above. Sending on other networks may result in loss of funds.
                            </div>

                            <p class="mb-0"><small>After sending the payment, please upload the proof of payment below.</small></p>
                        </div>

                        <div class="form-group">
                            <label for="network">Network Used</label>
                            <select name="network" class="form-control <?php echo (!empty($network_err)) ? 'is-invalid' : ''; ?>" required>
                                <option value="">-- Select Network --</option>
                                <?php foreach ($wallet_networks as $network_key => $network): ?>
                                    <option value="<?php echo $network_key; ?>" <?php echo ($network_key == $network) ? 'selected' : ''; ?>>
                                        <?php echo $network['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="invalid-feedback"><?php echo $network_err; ?></span>
                            <small class="form-text text-muted">Select the network you used to send the payment.</small>
                        </div>

                        <div class="form-group">
                            <label for="reference">Transaction Hash</label>
                            <input type="text" name="reference" id="tx-hash" class="form-control <?php echo (!empty($reference_err)) ? 'is-invalid' : ''; ?>"
                                   value="<?php echo $reference; ?>" placeholder="Enter the transaction hash (e.g., 0x1234...abcd)"
                                   maxlength="66">
                            <span class="invalid-feedback"><?php echo $reference_err; ?></span>
                            <small class="form-text text-muted">
                                Enter the full transaction hash from your wallet (starts with 0x followed by 64 characters).
                                <a href="#" data-toggle="modal" data-target="#txHashHelpModal">
                                    <i class="fas fa-question-circle"></i> How to find your transaction hash
                                </a>
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="proof_file">Upload Proof of Payment (Optional)</label>
                            <div class="custom-file">
                                <input type="file" name="proof_file" class="custom-file-input <?php echo (!empty($file_err)) ? 'is-invalid' : ''; ?>" id="proof_file" accept=".jpg,.jpeg,.png,.pdf">
                                <label class="custom-file-label" for="proof_file">Choose file</label>
                                <span class="invalid-feedback"><?php echo $file_err; ?></span>
                            </div>
                            <small class="form-text text-muted">
                                Accepted formats: JPG, PNG, PDF. Max size: 5MB.
                                <span class="text-info">The transaction hash is sufficient, but a screenshot can help in case of issues.</span>
                            </small>
                        </div>

                        <div class="form-group">
                            <img id="image-preview" class="img-fluid mb-3" style="display: none; max-height: 200px;">
                        </div>

                        <div class="form-group">
                            <button type="submit" name="buy_tokens" class="btn btn-primary">Submit Purchase</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Transaction History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Reference</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No transactions found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo format_date($transaction->created_at); ?></td>
                                    <td><?php echo format_currency($transaction->amount, 'Tokens'); ?></td>
                                    <td>
                                        <?php
                                        switch ($transaction->transaction_type) {
                                            case 'purchase':
                                                echo '<span class="badge badge-primary">Purchase</span>';
                                                break;
                                            case 'pledge':
                                                echo '<span class="badge badge-info">Pledge</span>';
                                                break;
                                            case 'refund':
                                                echo '<span class="badge badge-success">Refund</span>';
                                                break;
                                            case 'admin_credit':
                                                echo '<span class="badge badge-warning">Admin Credit</span>';
                                                break;
                                            default:
                                                echo '<span class="badge badge-secondary">Other</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $transaction->reference; ?></td>
                                    <td>
                                        <?php
                                        switch ($transaction->status) {
                                            case 'pending':
                                                echo '<span class="badge badge-warning">Pending</span>';
                                                break;
                                            case 'confirmed':
                                                echo '<span class="badge badge-success">Confirmed</span>';
                                                break;
                                            case 'rejected':
                                                echo '<span class="badge badge-danger">Rejected</span>';
                                                break;
                                            default:
                                                echo '<span class="badge badge-secondary">Unknown</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Hash Help Modal -->
<div class="modal fade" id="txHashHelpModal" tabindex="-1" role="dialog" aria-labelledby="txHashHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="txHashHelpModalLabel">How to Find Your Transaction Hash</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>A transaction hash (or TX hash) is a unique identifier that is generated whenever a transaction is executed on a blockchain network. It looks like a long string of letters and numbers starting with "0x".</p>

                <div class="alert alert-info">
                    <strong>Example:</strong> 0x1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef
                </div>

                <h6>How to find your transaction hash:</h6>

                <div class="accordion" id="walletAccordion">
                    <!-- MetaMask -->
                    <div class="card">
                        <div class="card-header" id="headingMetaMask">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapseMetaMask" aria-expanded="true" aria-controls="collapseMetaMask">
                                    MetaMask
                                </button>
                            </h2>
                        </div>
                        <div id="collapseMetaMask" class="collapse show" aria-labelledby="headingMetaMask" data-parent="#walletAccordion">
                            <div class="card-body">
                                <ol>
                                    <li>Open your MetaMask wallet</li>
                                    <li>Click on the "Activity" tab</li>
                                    <li>Find and click on the transaction you made</li>
                                    <li>Click on "View on block explorer"</li>
                                    <li>On the block explorer page, find the "Transaction Hash" field</li>
                                    <li>Copy the complete hash (starts with 0x)</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Trust Wallet -->
                    <div class="card">
                        <div class="card-header" id="headingTrust">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseTrust" aria-expanded="false" aria-controls="collapseTrust">
                                    Trust Wallet
                                </button>
                            </h2>
                        </div>
                        <div id="collapseTrust" class="collapse" aria-labelledby="headingTrust" data-parent="#walletAccordion">
                            <div class="card-body">
                                <ol>
                                    <li>Open your Trust Wallet app</li>
                                    <li>Go to the token you sent (USDT)</li>
                                    <li>Find and tap on the transaction</li>
                                    <li>Tap on "View on Explorer"</li>
                                    <li>On the block explorer page, find the "Transaction Hash" or "Txn Hash" field</li>
                                    <li>Copy the complete hash (starts with 0x)</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Binance -->
                    <div class="card">
                        <div class="card-header" id="headingBinance">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseBinance" aria-expanded="false" aria-controls="collapseBinance">
                                    Binance
                                </button>
                            </h2>
                        </div>
                        <div id="collapseBinance" class="collapse" aria-labelledby="headingBinance" data-parent="#walletAccordion">
                            <div class="card-body">
                                <ol>
                                    <li>Log in to your Binance account</li>
                                    <li>Go to "Wallet" > "Spot Wallet"</li>
                                    <li>Click on "Transaction History"</li>
                                    <li>Find your withdrawal transaction</li>
                                    <li>Click on "View Transaction"</li>
                                    <li>Copy the "Txn Hash" or "Transaction Hash"</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Other Wallets -->
                    <div class="card">
                        <div class="card-header" id="headingOther">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseOther" aria-expanded="false" aria-controls="collapseOther">
                                    Other Wallets
                                </button>
                            </h2>
                        </div>
                        <div id="collapseOther" class="collapse" aria-labelledby="headingOther" data-parent="#walletAccordion">
                            <div class="card-body">
                                <p>For other wallets, the general process is:</p>
                                <ol>
                                    <li>Find your transaction history or activity</li>
                                    <li>Locate the specific transaction you made to our wallet address</li>
                                    <li>Look for an option to view transaction details or view on block explorer</li>
                                    <li>Find the field labeled "Transaction Hash", "Txn Hash", or "TX ID"</li>
                                    <li>Copy the complete hash (starts with 0x)</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning mt-3">
                    <strong>Important:</strong> Make sure you're copying the transaction hash for the correct transaction. The transaction should be:
                    <ul>
                        <li>A USDT transfer</li>
                        <li>Sent to our wallet address</li>
                        <li>For the exact amount you're claiming</li>
                        <li>On the network you selected</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(button) {
    var input = button.parentNode.previousElementSibling;
    input.select();
    document.execCommand("copy");
    button.textContent = "Copied!";
    setTimeout(function() {
        button.textContent = "Copy";
    }, 2000);
}

// Transaction hash validation
document.addEventListener('DOMContentLoaded', function() {
    const txHashInput = document.getElementById('tx-hash');

    if (txHashInput) {
        // Format and validate transaction hash on input
        txHashInput.addEventListener('input', function(e) {
            let value = this.value;

            // Convert to lowercase
            value = value.toLowerCase();

            // Ensure it starts with 0x if user types something
            if (value.length > 0 && !value.startsWith('0x')) {
                // If user pastes a hash without 0x, add it
                if (value.length >= 64 && /^[a-f0-9]{64,}$/.test(value)) {
                    value = '0x' + value;
                }
                // If user is typing and hasn't added 0x, don't modify yet
            }

            // If it starts with 0x, validate the rest as hex
            if (value.startsWith('0x')) {
                const hexPart = value.substring(2);
                const validHex = hexPart.replace(/[^a-f0-9]/g, '');

                // Limit to 64 hex characters after 0x
                if (validHex.length > 64) {
                    value = '0x' + validHex.substring(0, 64);
                } else {
                    value = '0x' + validHex;
                }
            }

            // Update the input value if it changed
            if (this.value !== value) {
                this.value = value;
            }
        });

        // Form validation
        const form = txHashInput.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const txHash = txHashInput.value;

                // Check if the hash is in the correct format (0x + 64 hex chars)
                if (!txHash.match(/^0x[a-f0-9]{64}$/)) {
                    e.preventDefault();
                    txHashInput.classList.add('is-invalid');

                    // Create or update error message
                    let errorMsg = txHashInput.nextElementSibling;
                    if (!errorMsg || !errorMsg.classList.contains('invalid-feedback')) {
                        errorMsg = document.createElement('div');
                        errorMsg.className = 'invalid-feedback';
                        txHashInput.parentNode.appendChild(errorMsg);
                    }

                    if (!txHash.startsWith('0x')) {
                        errorMsg.textContent = 'Transaction hash must start with 0x.';
                    } else if (txHash.length !== 66) {
                        errorMsg.textContent = 'Transaction hash must be exactly 66 characters (0x + 64 hexadecimal characters).';
                    } else {
                        errorMsg.textContent = 'Transaction hash must contain only hexadecimal characters after 0x (0-9, a-f).';
                    }
                    return false;
                }
            });
        }
    }
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>
