<?php

namespace Softspring\CmsTranslationPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sfs_cms_translation');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('api')
                    ->canBeEnabled()
                    ->children()
                        ->enumNode('driver')->values(['google'])->defaultValue('google')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
