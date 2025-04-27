<?php
// Wallet addresses for token purchases
define('WALLET_ADDRESS', '0xYourWalletAddressHere'); // Legacy support

// Network-specific wallet addresses
$wallet_networks = [
    'ETHEREUM' => [
        'name' => 'Ethereum (ERC-20)',
        'address' => '0xYourEthereumWalletAddressHere',
        'icon' => 'fab fa-ethereum'
    ],
    'BSC' => [
        'name' => 'Binance Smart Chain (BEP-20)',
        'address' => '0xYourBSCWalletAddressHere',
        'icon' => 'fas fa-coins'
    ],
    'ARBITRUM' => [
        'name' => 'Arbitrum',
        'address' => '0xYourArbitrumWalletAddressHere',
        'icon' => 'fas fa-network-wired'
    ],
    'OPTIMISM' => [
        'name' => 'Optimism',
        'address' => '0xYourOptimismWalletAddressHere',
        'icon' => 'fas fa-bolt'
    ]
];
?>
