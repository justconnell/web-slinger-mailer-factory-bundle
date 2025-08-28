<?php

namespace WebSlinger\MailerFactory;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Throwable;

class MailerFactory
{
    private bool $isTest = false;

    public function __construct(
        private readonly TransportInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $testEmail,
        private readonly string $apiEnv,
        private readonly string $uploadDirectory,
        private readonly string $subjectPrefix = 'TEST EMAIL: ',
        private readonly bool $enableErrorLogging = true,
    ) {
        // Determine if we're in test mode
        if (strtoupper($this->apiEnv) !== 'PROD') {
            $this->isTest = true;
        }
    }

    /**
     * Send a templated email with optional CC and priority
     */
    public function sendTemplatedEmail(
        string|array $to,
        string $subject,
        string $templatePath,
        array $context,
        string|array|null $cc = null,
        ?int $priority = Email::PRIORITY_NORMAL,
    ): void {
        $email = $this->formatTemplatedEmail($to, $subject, $templatePath, $context, $cc);
        $email->priority($priority);
        $this->send($email);
    }

    /**
     * Format a templated email with the provided parameters
     */
    private function formatTemplatedEmail(
        string|array $to,
        string $subject,
        string $templatePath,
        array $context,
        string|array|null $cc = null,
    ): TemplatedEmail {
        // Add test mode flag to context
        if ($this->isTest) {
            $context['isTest'] = true;
        }

        $email = (new TemplatedEmail())
            ->to(...$this->setRecipient($to))
            ->subject($this->formatSubject($subject))
            ->htmlTemplate($templatePath)
            ->context($context);

        // Handle attachments if present in context
        if (!empty($context['attachments'])) {
            $this->processAttachments($email, $context['attachments']);
        }

        // Add CC recipients if provided
        if ($cc !== null) {
            $email->cc(...$this->setRecipient($cc));
        }

        return $email;
    }

    /**
     * Process and add attachments to email
     */
    private function processAttachments(TemplatedEmail $email, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            if (isset($attachment['file'], $attachment['name'], $attachment['mime'])) {
                $email->addPart(
                    new DataPart(fopen($attachment['file'], 'r'), $attachment['name'], $attachment['mime'])
                );
                
                // Clean up temporary file
                if (file_exists($attachment['file'])) {
                    unlink($attachment['file']);
                }
            }
        }
    }

    /**
     * Set and validate email recipients
     */
    private function setRecipient(array|string|null $emailTo = null): array
    {
        if (!$emailTo) {
            return [$this->testEmail];
        }

        $recipients = is_array($emailTo) ? $emailTo : [$emailTo];
        $validRecipients = [];
        $validator = new EmailValidator();

        foreach ($recipients as $email) {
            if ($email && $validator->isValid($email, new RFCValidation())) {
                $validRecipients[] = $email;
            }
        }

        // Fallback to test email if no valid recipients
        return !empty($validRecipients) ? $validRecipients : [$this->testEmail];
    }

    /**
     * Format email subject with test prefix if in test mode
     */
    private function formatSubject(string $subject): string
    {
        return $this->isTest ? $this->subjectPrefix . $subject : $subject;
    }

    /**
     * Send the email and handle errors
     */
    private function send(TemplatedEmail $email): void
    {
        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface|Throwable $e) {
            if ($this->enableErrorLogging) {
                $this->logger->error('Unable to send Email: ' . $e->getMessage(), [
                    'exception' => $e,
                    'subject' => $email->getSubject(),
                    'to' => array_map(fn($addr) => $addr->getAddress(), $email->getTo()),
                ]);
            }

            // Re-throw the exception so calling code can handle it
            throw $e;
        }
    }

    /**
     * Format an attachment from encoded file data
     */
    public function formatAttachment(string $encodedFile, string $fileName): ?array
    {
        if (empty($encodedFile) || empty($fileName)) {
            return null;
        }

        // Parse the data URL
        $parts = explode(',', $encodedFile, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$prefix, $base64] = $parts;
        
        // Extract MIME type
        $mimeMatch = preg_match('/data:([^;]+)/', $prefix, $matches);
        if (!$mimeMatch) {
            return null;
        }
        
        $mime = $matches[1];
        $decodedFile = base64_decode($base64, true);

        if ($decodedFile === false) {
            return null;
        }

        // Ensure upload directory exists
        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0755, true);
        }

        $filePath = $this->uploadDirectory . $fileName;
        
        if (file_put_contents($filePath, $decodedFile) === false) {
            return null;
        }

        return [
            'mime' => $mime,
            'name' => $fileName,
            'file' => $filePath,
        ];
    }

    /**
     * Check if the mailer is in test mode
     */
    public function isTestMode(): bool
    {
        return $this->isTest;
    }

    /**
     * Get the current test email address
     */
    public function getTestEmail(): string
    {
        return $this->testEmail;
    }
}
