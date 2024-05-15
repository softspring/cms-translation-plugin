<?php

namespace Softspring\CmsTranslationPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SfsCmsTranslationExtension extends Extension // implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config/services'));

        if ($config['api']['enabled']) {
            $driver = $config['api']['driver'];
            $loader->load('api.yaml');
            $loader->load("api_driver/$driver.yaml");

            $container->setParameter('sfs_cms_translation.api.driver', $driver);
        }

        $container->setParameter('sfs_cms_translation.api.enabled', $config['api']['enabled']);

        // load services
        $loader->load('services.yaml');
        $loader->load('controller/admin_content_version.yaml');
    }
}
