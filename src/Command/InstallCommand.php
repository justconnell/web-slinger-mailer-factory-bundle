<?php

namespace WebSlinger\MailerFactory\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'web-slinger:mailer-setup',
    description: 'Set up the WebSlinger Mailer Factory Bundle configuration files',
)]
class InstallCommand extends Command
{
    public function __construct(private string $projectDir)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('WebSlinger Mailer Factory Bundle Setup');

        $this->createConfigFile($io);
        $this->updateEnvFile($io);
        $this->createUploadDirectory($io);
        
        $io->success('WebSlinger Mailer Factory Bundle has been configured successfully!');
        $io->note([
            'Please configure the following environment variables in your .env file:',
            '  WEB_SLINGER_MAILER_TEST_EMAIL=your-test-email@example.com',
            '  WEB_SLINGER_MAILER_UPLOAD_DIR=/path/to/upload/directory (optional)',
        ]);
        
        return Command::SUCCESS;
    }

    private function createConfigFile(SymfonyStyle $io): void
    {
        $configDir = $this->projectDir . '/config/packages';
        $configFile = $configDir . '/web_slinger_mailer.yaml';

        if (!is_dir($configDir)) {
            if (!mkdir($configDir, 0755, true)) {
                $io->error('Could not create config directory: ' . $configDir);
                return;
            }
        }

        if (file_exists($configFile)) {
            $io->note('Configuration file already exists: ' . $configFile);
            return;
        }

        $configContent = $this->getConfigTemplate();
        
        if (file_put_contents($configFile, $configContent) === false) {
            $io->error('Could not create configuration file: ' . $configFile);
            return;
        }

        $io->text('✓ Created configuration file: ' . $configFile);
    }

    private function updateEnvFile(SymfonyStyle $io): void
    {
        $envFile = $this->projectDir . '/.env';
        $envLocalFile = $this->projectDir . '/.env.local';
        
        $envContent = $this->getEnvTemplate();

        // Check if variables already exist in .env
        if (file_exists($envFile)) {
            $existingContent = file_get_contents($envFile);
            if (strpos($existingContent, 'WEB_SLINGER_MAILER_TEST_EMAIL') !== false) {
                $io->note('Environment variables already exist in .env file');
                return;
            }
        }

        // Try to append to .env.local first, then .env
        $targetFile = file_exists($envLocalFile) ? $envLocalFile : $envFile;
        
        if (file_exists($targetFile)) {
            $existingContent = file_get_contents($targetFile);
            // Add newline if file doesn't end with one
            if (!empty($existingContent) && substr($existingContent, -1) !== "\n") {
                $envContent = "\n" . $envContent;
            }
            
            if (file_put_contents($targetFile, $envContent, FILE_APPEND | LOCK_EX) === false) {
                $io->error('Could not update environment file: ' . $targetFile);
                return;
            }
        } else {
            if (file_put_contents($targetFile, $envContent) === false) {
                $io->error('Could not create environment file: ' . $targetFile);
                return;
            }
        }

        $io->text('✓ Updated environment variables in: ' . $targetFile);
    }

    private function createUploadDirectory(SymfonyStyle $io): void
    {
        $uploadDir = $this->projectDir . '/var/uploads';
        
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $io->warning('Could not create upload directory: ' . $uploadDir);
                return;
            }
            $io->text('✓ Created upload directory: ' . $uploadDir);
        } else {
            $io->text('✓ Upload directory already exists: ' . $uploadDir);
        }
    }

    private function getConfigTemplate(): string
    {
        return <<<YAML
# WebSlinger Mailer Factory Bundle Configuration
web_slinger_mailer_factory:
    mailer_factory:
        test_email: '%env(WEB_SLINGER_MAILER_TEST_EMAIL)%'
        api_env: '%env(APP_ENV)%'
        upload_directory: '%env(default:kernel.project_dir:/var/uploads/:WEB_SLINGER_MAILER_UPLOAD_DIR)%'
        subject_prefix: 'TEST EMAIL: '
        enable_error_logging: true
YAML;
    }

    private function getEnvTemplate(): string
    {
        return <<<ENV

###> web-slinger/mailer-factory-bundle ###
# Configure your mailer factory settings
WEB_SLINGER_MAILER_TEST_EMAIL=test@example.com
# WEB_SLINGER_MAILER_UPLOAD_DIR=/custom/upload/path (optional)
###< web-slinger/mailer-factory-bundle ###
ENV;
    }
}
