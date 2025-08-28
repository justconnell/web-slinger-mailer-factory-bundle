# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-08-28

### Added
- Initial release of WebSlinger Mailer Factory Bundle
- Test mode functionality for non-production environments
- Templated email support with Twig integration
- Attachment handling with base64 data support
- Email validation using egulias/email-validator
- Comprehensive error handling and logging
- CC recipient support
- Email priority support
- Automatic setup command for configuration
- Full Symfony 5.4+ compatibility

### Features
- `MailerFactory` service for sending templated emails
- `formatAttachment()` method for handling file attachments
- Automatic test email redirection in non-production environments
- Configurable upload directory and email settings
- Integration with Symfony's dependency injection container
- Console command for easy bundle setup
