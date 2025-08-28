<?php

namespace WebSlinger\MailerFactory\Tests;

use PHPUnit\Framework\TestCase;
use WebSlinger\MailerFactory\MailerFactory;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Psr\Log\LoggerInterface;

class MailerFactoryTest extends TestCase
{
    private MailerFactory $mailerFactory;
    private TransportInterface $transport;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->mailerFactory = new MailerFactory(
            mailer: $this->transport,
            logger: $this->logger,
            testEmail: 'test@example.com',
            apiEnv: 'test',
            uploadDirectory: '/tmp/test_uploads/',
            subjectPrefix: 'TEST: ',
            enableErrorLogging: true
        );
    }

    public function testIsTestModeReturnsTrueForNonProdEnv(): void
    {
        $this->assertTrue($this->mailerFactory->isTestMode());
    }

    public function testGetTestEmailReturnsConfiguredEmail(): void
    {
        $this->assertEquals('test@example.com', $this->mailerFactory->getTestEmail());
    }

    public function testFormatAttachmentWithValidData(): void
    {
        $base64Data = 'data:text/plain;base64,' . base64_encode('test content');
        $fileName = 'test.txt';

        $result = $this->mailerFactory->formatAttachment($base64Data, $fileName);

        $this->assertIsArray($result);
        $this->assertEquals('text/plain', $result['mime']);
        $this->assertEquals($fileName, $result['name']);
        $this->assertStringContainsString($fileName, $result['file']);
    }

    public function testFormatAttachmentWithInvalidData(): void
    {
        $result = $this->mailerFactory->formatAttachment('invalid-data', 'test.txt');
        $this->assertNull($result);
    }

    public function testFormatAttachmentWithEmptyData(): void
    {
        $result = $this->mailerFactory->formatAttachment('', 'test.txt');
        $this->assertNull($result);

        $result = $this->mailerFactory->formatAttachment('data:text/plain;base64,dGVzdA==', '');
        $this->assertNull($result);
    }
}
