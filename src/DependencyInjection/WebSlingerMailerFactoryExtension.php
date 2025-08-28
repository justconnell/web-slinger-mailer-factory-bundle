<?php

namespace WebSlinger\MailerFactory\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class WebSlingerMailerFactoryExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        // Set parameters for the MailerFactory service
        $mailerConfig = $config['mailer_factory'] ?? [];
        $container->setParameter('webslinger.mailer_factory.test_email', $mailerConfig['test_email'] ?? 'test@example.com');
        $container->setParameter('webslinger.mailer_factory.api_env', $mailerConfig['api_env'] ?? '%env(APP_ENV)%');
        $container->setParameter('webslinger.mailer_factory.upload_directory', $mailerConfig['upload_directory'] ?? '%kernel.project_dir%/var/uploads/');
        $container->setParameter('webslinger.mailer_factory.subject_prefix', $mailerConfig['subject_prefix'] ?? 'TEST EMAIL: ');
        $container->setParameter('webslinger.mailer_factory.enable_error_logging', $mailerConfig['enable_error_logging'] ?? true);
    }

    public function getAlias(): string
    {
        return 'web_slinger_mailer_factory';
    }
}
