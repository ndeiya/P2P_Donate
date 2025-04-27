# Contributing to P2P Donate

Thank you for considering contributing to P2P Donate! This document outlines the process for contributing to the project.

## Code of Conduct

By participating in this project, you agree to abide by our code of conduct:

- Be respectful and inclusive
- Be patient and welcoming
- Be considerate
- Be collaborative
- Be careful in the words you choose
- When we disagree, try to understand why

## How Can I Contribute?

### Reporting Bugs

- Check if the bug has already been reported in the Issues section
- Use the bug report template when creating a new issue
- Include detailed steps to reproduce the bug
- Include screenshots if applicable
- Describe what you expected to happen
- Describe what actually happened

### Suggesting Enhancements

- Check if the enhancement has already been suggested in the Issues section
- Use the feature request template when creating a new issue
- Provide a clear and detailed explanation of the feature
- Explain why this enhancement would be useful to most users
- List some examples of how this feature would be used

### Pull Requests

1. Fork the repository
2. Create a new branch (`git checkout -b feature/your-feature-name`)
3. Make your changes
4. Run tests if available
5. Commit your changes (`git commit -m 'Add some feature'`)
6. Push to the branch (`git push origin feature/your-feature-name`)
7. Open a Pull Request

## Development Setup

1. Clone the repository
2. Create a MySQL database
3. Copy `config/config.sample.php` to `config/config.php` and update the settings
4. Copy `config/wallet.sample.php` to `config/wallet.php` and update the wallet addresses
5. Import the database schema from `database/schema.sql`
6. Set appropriate permissions for the `uploads` directory

## Coding Standards

- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Add comments for complex logic
- Keep functions small and focused on a single task
- Write clean, readable, and maintainable code

## Testing

- Test your changes thoroughly before submitting a pull request
- Ensure your changes don't break existing functionality
- Add new test cases for new features if applicable

## Documentation

- Update the README.md file if necessary
- Document new features or changes in behavior
- Update inline documentation for functions and classes

## Questions?

If you have any questions about contributing, please open an issue with your question.

Thank you for contributing to P2P Donate!
