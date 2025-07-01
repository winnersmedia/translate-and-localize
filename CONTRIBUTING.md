# Contributing to Translate and Localize with Grok

Thank you for your interest in contributing to this WordPress plugin! We welcome contributions from the community.

## How to Contribute

### Reporting Issues

1. **Search existing issues** to avoid duplicates
2. **Create a new issue** with a clear title and description
3. **Include steps to reproduce** for bugs
4. **Provide environment details** (WordPress version, PHP version, Polylang version)
5. **Add screenshots** if relevant

### Suggesting Features

1. **Open an issue** with the "enhancement" label
2. **Describe the feature** and its use case
3. **Explain the benefit** to users
4. **Consider implementation** complexity

### Pull Requests

1. **Fork the repository**
2. **Create a feature branch** from `main`
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. **Make your changes** following coding standards
4. **Test thoroughly** with different WordPress/PHP versions
5. **Update documentation** if needed
6. **Submit a pull request** with clear description

## Coding Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Use meaningful variable and function names
- Add inline documentation for complex logic
- Ensure code is secure and escaped properly
- Test with `WP_DEBUG` enabled

## Testing

Before submitting:

1. Test with latest WordPress version
2. Test with minimum supported WordPress version (5.0)
3. Verify Polylang compatibility
4. Check for PHP errors/warnings
5. Test translation functionality
6. Verify queue processing works
7. Test with different Grok models

## Development Setup

1. Clone the repository
2. Set up a local WordPress environment
3. Install and activate Polylang
4. Configure Grok API credentials
5. Enable `WP_DEBUG` in `wp-config.php`

## Questions?

Feel free to open an issue for any questions about contributing.

## License

By contributing, you agree that your contributions will be licensed under the same GPL v2 license as the project.