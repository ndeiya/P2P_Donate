# P2P-Donate Platform

P2P-Donate is a peer-to-peer donation platform that facilitates direct transfers between users. The platform matches donors with recipients and provides a system for verifying transfers, operating on a "give one, receive two" model.

## Features

- User registration and authentication
- Token-based system for platform operations
- Peer-to-peer pledge system (give one, receive two)
- Mobile money and cryptocurrency payment support
- Dark mode for better user experience
- Responsive design optimized for mobile devices
- Payment verification with optional proof uploads
- Chat system for communication between matched users
- Referral system for user acquisition
- Dispute resolution system
- Admin panel for platform management
- Comprehensive legal documentation (Terms of Service, Privacy Policy, Disclaimer)

## Technology Stack

- PHP 7.4+
- MySQL 5.7+ Database
- HTML5, CSS3, JavaScript
- Bootstrap 4.5
- jQuery
- Font Awesome 5
- Responsive design framework

## Installation

### Prerequisites

- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer (optional, for future dependencies)

### Setup Instructions

1. Clone the repository to your local machine or web server:
   ```
   git clone https://github.com/yourusername/p2p-donate.git
   ```

2. Create a MySQL database named `p2p_donate`

3. Copy the `config/config.sample.php` to `config/config.php` and update the database credentials and other settings:
   ```
   cp config/config.sample.php config/config.php
   ```

4. Import the database schema:
   ```
   mysql -u username -p p2p_donate < database/schema.sql
   ```

5. Set appropriate permissions:
   ```
   chmod 755 -R ./
   chmod 777 -R ./uploads
   ```

6. Configure your web server to point to the project's root directory

7. Access the platform through your web browser

## Default Admin Credentials

- Email: admin@p2pdonate.com
- Password: admin123

## Directory Structure

- `admin/` - Admin panel files
- `assets/` - CSS, JavaScript, and image files
- `config/` - Configuration files
- `controllers/` - Controller files for handling form submissions
- `database/` - Database connection and schema files
- `includes/` - Reusable PHP functions and components
- `uploads/` - Directory for uploaded files
- `views/` - View templates

## Pledge System

The platform operates on a "give one, receive two" model:

1. User makes a pledge of a fixed amount (GHS 200)
2. User pays a platform fee of 10 tokens
3. User is matched with a recipient and sends the payment directly
4. After making a pledge, the user is placed in queue to receive two pledges
5. After receiving two pledges, the user can make another pledge to rejoin the queue

## Payment Methods

The platform supports the following payment methods:

1. **Mobile Money**: Direct transfers between users via mobile money
2. **Cryptocurrency**: USDT payments on the following networks:
   - Ethereum (ERC-20)
   - Binance Smart Chain (BEP-20)
   - Arbitrum
   - Optimism

## User Flow

1. User registers an account
2. User purchases tokens using USDT (1 USDT = 10 tokens)
3. User makes a pledge (GHS 200) and pays a platform fee (10 tokens)
4. System matches the pledge with a recipient
5. User sends payment to the recipient via mobile money
6. User uploads proof of payment (optional)
7. Recipient confirms receipt
8. Pledge is marked as completed
9. User is placed in queue to receive two pledges

## Admin Features

- User management
- Pledge and match management
- Token management
- Transaction logs
- Dispute resolution
- System settings

## Configuration

The main configuration file is `config/config.php`. You need to set the following:

- Database credentials
- Site name and URL
- Token rate (1 USDT = 10 tokens)
- Pledge amount (GHS 200)
- Session settings
- File upload settings
- Wallet addresses for cryptocurrency payments

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please contact [Your Contact Email].

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Security

- The platform includes input validation and sanitization
- Passwords are stored using secure hashing
- CSRF protection is implemented for forms
- File upload validation is in place
- Session security measures are implemented
