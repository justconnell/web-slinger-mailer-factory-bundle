# WebSlinger Mailer Factory Bundle

A reusable factory for sending templated emails with Symfony Mailer, featuring test mode, attachment support, and comprehensive error handling.

## Features

- **Test Mode**: Automatically redirect emails to a test address in non-production environments
- **Templated Emails**: Full support for Twig templates with context variables
- **Attachment Support**: Easy handling of file attachments with automatic cleanup
- **Email Validation**: Built-in email address validation using egulias/email-validator
- **Error Handling**: Comprehensive error logging and handling
- **CC Support**: Send emails with CC recipients
- **Priority Support**: Set email priority levels
- **Configurable**: Fully configurable through Symfony configuration

## Installation

Install the package via Composer:

```bash
composer require web-slinger/mailer-factory-bundle
```

## Configuration

1. **Register the bundle** in your `config/bundles.php`:

```php
<?php

return [
    // ... other bundles
    WebSlinger\MailerFactory\WebSlingerMailerFactoryBundle::class => ['all' => true],
];
```

2. **Run the setup command** to create configuration files:

```bash
php bin/console web-slinger:mailer-setup
```

This will:
- Create `config/packages/web_slinger_mailer.yaml` with the bundle configuration
- Add environment variables to your `.env` file
- Create the upload directory for attachments

3. **Configure your settings** by updating the environment variables in your `.env` file:

```bash
# Configure your mailer factory settings
WEB_SLINGER_MAILER_TEST_EMAIL=your-test-email@example.com
# WEB_SLINGER_MAILER_UPLOAD_DIR=/custom/upload/path (optional)
```

### Alternative Manual Setup

If you prefer to set up manually, create `config/packages/web_slinger_mailer.yaml`:

```yaml
# WebSlinger Mailer Factory Bundle Configuration
webslinger:
    mailer_factory:
        test_email: '%env(WEB_SLINGER_MAILER_TEST_EMAIL)%'
        api_env: '%env(APP_ENV)%'
        upload_directory: '%kernel.project_dir%/var/uploads/'
        subject_prefix: 'TEST EMAIL: '
        enable_error_logging: true
```

And add the environment variables to your `.env` file:

```bash
###> web-slinger/mailer-factory-bundle ###
# Configure your mailer factory settings
WEB_SLINGER_MAILER_TEST_EMAIL=test@example.com
# WEB_SLINGER_MAILER_UPLOAD_DIR=/custom/upload/path (optional)
###< web-slinger/mailer-factory-bundle ###
```

## Usage

### Basic Usage

Inject the `MailerFactory` service into your controllers or services:

```php
<?php

namespace App\Controller;

use WebSlinger\MailerFactory\MailerFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class EmailController extends AbstractController
{
    public function __construct(
        private MailerFactory $mailerFactory
    ) {}

    public function sendWelcomeEmail(): Response
    {
        $this->mailerFactory->sendTemplatedEmail(
            to: 'user@example.com',
            subject: 'Welcome to Our Platform!',
            templatePath: 'emails/welcome.html.twig',
            context: [
                'user_name' => 'John Doe',
                'activation_link' => 'https://example.com/activate/123'
            ]
        );

        return $this->json(['status' => 'Email sent successfully']);
    }
}
```

### Advanced Usage

```php
public function sendInvoiceEmail(): Response
{
    // Format an attachment from base64 data
    $attachment = $this->mailerFactory->formatAttachment(
        $base64EncodedFile,
        'invoice.pdf'
    );

    $context = [
        'customer_name' => 'Jane Smith',
        'invoice_number' => 'INV-2024-001',
        'amount' => '$150.00',
    ];

    // Add attachment to context if formatting was successful
    if ($attachment) {
        $context['attachments'] = [$attachment];
    }

    $this->mailerFactory->sendTemplatedEmail(
        to: ['customer@example.com', 'billing@example.com'],
        subject: 'Your Invoice is Ready',
        templatePath: 'emails/invoice.html.twig',
        context: $context,
        cc: 'accounting@ourcompany.com',
        priority: Email::PRIORITY_HIGH
    );

    return $this->json(['status' => 'Invoice email sent successfully']);
}
```

### Multiple Recipients and CC

```php
$this->mailerFactory->sendTemplatedEmail(
    to: ['user1@example.com', 'user2@example.com'],
    subject: 'Team Update',
    templatePath: 'emails/team_update.html.twig',
    context: ['update_content' => 'Monthly progress report...'],
    cc: ['manager@example.com', 'hr@example.com']
);
```

### Handling Attachments

The bundle provides a convenient method to format attachments from base64-encoded data:

```php
// Format attachment from base64 data URL (e.g., from file uploads)
$attachment = $this->mailerFactory->formatAttachment(
    'data:application/pdf;base64,JVBERi0xLjQKJdPr6eEKMSAwIG9iago8PC9UeXBlL0NhdGFsb2...',
    'document.pdf'
);

if ($attachment) {
    $context['attachments'] = [$attachment];
    // The attachment will be automatically cleaned up after sending
}
```

## Method Parameters

### `sendTemplatedEmail()` Parameters

- `to` (string|array): Email recipient(s)
- `subject` (string): Email subject line
- `templatePath` (string): Path to Twig template
- `context` (array): Variables to pass to the template
- `cc` (string|array|null): CC recipient(s) (optional)
- `priority` (?int): Email priority (optional, defaults to `Email::PRIORITY_NORMAL`)

### `formatAttachment()` Parameters

- `encodedFile` (string): Base64-encoded file data (data URL format)
- `fileName` (string): Desired filename for the attachment

Returns an array with `mime`, `name`, and `file` keys, or `null` if formatting fails.

## Configuration Options

The bundle supports the following configuration options:

```yaml
webslinger:
    mailer_factory:
        test_email: 'test@example.com'           # Test email address for non-production
        api_env: '%env(APP_ENV)%'                # Environment variable to check for test mode
        upload_directory: '%kernel.project_dir%/var/uploads/'  # Directory for temporary files
        subject_prefix: 'TEST EMAIL: '           # Prefix for test mode emails
        enable_error_logging: true               # Enable/disable error logging
```

## Test Mode

The bundle automatically detects non-production environments and enables test mode when `APP_ENV` is not `PROD`. In test mode:

- All emails are redirected to the configured test email address
- Email subjects are prefixed with the configured prefix (default: "TEST EMAIL: ")
- The `isTest` flag is added to the template context

### Checking Test Mode

You can check if the mailer is in test mode:

```php
if ($this->mailerFactory->isTestMode()) {
    // Handle test mode logic
    $testEmail = $this->mailerFactory->getTestEmail();
}
```

## Template Context

When using templated emails, the following variables are automatically available in your Twig templates:

- All variables from your `context` array
- `isTest` (boolean): True when in test mode

Example template (`emails/welcome.html.twig`):

```twig
<!DOCTYPE html>
<html>
<head>
    <title>Welcome Email</title>
</head>
<body>
    {% if isTest %}
        <div style="background: yellow; padding: 10px;">
            <strong>TEST MODE:</strong> This email would normally be sent to the actual recipient.
        </div>
    {% endif %}
    
    <h1>Welcome, {{ user_name }}!</h1>
    <p>Thank you for joining our platform.</p>
    <a href="{{ activation_link }}">Activate your account</a>
</body>
</html>
```

## Error Handling

The bundle includes comprehensive error handling:

- Failed email sends are logged with full context
- Transport exceptions are caught and logged
- Attachment processing errors are handled gracefully
- Invalid email addresses are filtered out automatically

### Custom Error Handling

You can catch and handle exceptions in your application:

```php
try {
    $this->mailerFactory->sendTemplatedEmail(
        to: 'user@example.com',
        subject: 'Important Update',
        templatePath: 'emails/update.html.twig',
        context: ['message' => 'Your account has been updated.']
    );
} catch (TransportExceptionInterface $e) {
    // Handle mailer transport errors
    $this->logger->error('Failed to send email: ' . $e->getMessage());
    return $this->json(['error' => 'Email delivery failed'], 500);
} catch (Throwable $e) {
    // Handle other errors (template not found, etc.)
    $this->logger->error('Email processing error: ' . $e->getMessage());
    return $this->json(['error' => 'Email processing failed'], 500);
}
```

## Requirements

- PHP >= 8.1
- Symfony >= 5.4
- Symfony Mailer component
- Twig (for templated emails)
- egulias/email-validator

## Migration from Original MailerFactory

If you're migrating from the original `App\Factory\MailerFactory`, here are the key changes:

### Constructor Changes
- Remove `ErrorLogService` dependency (now uses standard Symfony Logger)
- Remove `ParameterBagInterface` dependency (configuration is now handled by the bundle)
- Configuration is now handled through bundle configuration

### Method Changes
- Methods remain the same for basic usage
- Error handling is now done through standard Symfony logging
- `formatAttachment()` method signature remains the same

## License

MIT

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
