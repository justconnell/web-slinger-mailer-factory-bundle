<?php

namespace WebSlinger\MailerFactory\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('webslinger');

        $rootNode = $treeBuilder->getRootNode();
        
        $rootNode
            ->children()
                ->arrayNode('mailer_factory')
                    ->children()
                        ->scalarNode('test_email')
                            ->defaultValue('test@example.com')
                            ->info('Default test email address for non-production environments')
                        ->end()
                        ->scalarNode('api_env')
                            ->defaultValue('%env(APP_ENV)%')
                            ->info('API environment - when not PROD, test mode is enabled')
                        ->end()
                        ->scalarNode('upload_directory')
                            ->defaultValue('%kernel.project_dir%/var/uploads/')
                            ->info('Directory for temporary file uploads')
                        ->end()
                        ->scalarNode('subject_prefix')
                            ->defaultValue('TEST EMAIL: ')
                            ->info('Prefix added to email subjects in test mode')
                        ->end()
                        ->booleanNode('enable_error_logging')
                            ->defaultTrue()
                            ->info('Enable error logging for mailer failures')
                        ->end()
                    ->end()
                ->end()
                ->variableNode('stored_procedure')
                    ->info('Configuration for web-slinger stored procedure bundle')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
