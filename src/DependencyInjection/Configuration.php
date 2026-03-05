<?php

namespace Softspring\CmsTranslationPlugin\DependencyInjection;

use Softspring\CmsTranslationPlugin\SfsCmsTranslationPlugin;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(SfsCmsTranslationPlugin::getAlias());
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->end()
        ;

        return $treeBuilder;
    }
}
