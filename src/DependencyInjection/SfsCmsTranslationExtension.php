<?php

namespace Softspring\CmsTranslationPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SfsCmsTranslationExtension extends Extension // implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config/services'));

        // load services
        $loader->load('services.yaml');
        $loader->load('controller/admin_content_version.yaml');

        $registeredPlugins = $container->hasParameter('sfs_cms.registered_plugins') ? $container->getParameter('sfs_cms.registered_plugins') : [];
        foreach ($registeredPlugins as $plugin) {
            if ('sfs_cms_sections' === $plugin['alias']) {
                $loader->load('sfs_sections_plugin.yaml');
                $loader->load('controller/admin_section_version.yaml');
            }
        }
    }
}
