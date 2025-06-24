<?php

namespace Softspring\CmsTranslationPlugin\Config\Model;

use Softspring\CmsBundle\Config\Model\ConfigExtensionInterface;
use Softspring\CmsBundle\Config\Model\Content;
use Softspring\CmsTranslationPlugin\Form\Admin\VersionTranslateForm;
use Softspring\CmsTranslationPlugin\Form\Admin\VersionTranslationsImportForm;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

class ContentExtension implements ConfigExtensionInterface
{
    public function extend(NodeDefinition $rootNode): void
    {
        foreach ($rootNode->getChildNodeDefinitions() as $name => $node) {
            if ('admin' !== $name) {
                continue;
            }

            $node->append(
                (new ArrayNodeDefinition('version_translations'))
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('is_granted')->defaultValue('PERMISSION_SFS_CMS_ADMIN_CONTENT_TRANSLATIONS')->end()
                    ->scalarNode('view')->defaultValue('@SfsCmsTranslationPlugin/admin/content/version_translations.html.twig')->end()
                    ->scalarNode('type')->defaultValue(VersionTranslateForm::class)->end()
                    ->scalarNode('success_redirect_to')->defaultValue('')->end()
                ->end()
            );

            $node->append(
                (new ArrayNodeDefinition('version_translations_export'))
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('is_granted')->defaultValue('PERMISSION_SFS_CMS_ADMIN_CONTENT_TRANSLATIONS_EXPORT')->end()
                    ->arrayNode('formats')
                        ->defaultValue(['xliff12'])
                        ->enumPrototype()->values(['xliff12'])->end()
                    ->end()
                ->end()
            );

            $node->append(
                (new ArrayNodeDefinition('version_translations_import'))
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('is_granted')->defaultValue('PERMISSION_SFS_CMS_ADMIN_CONTENT_TRANSLATIONS_IMPORT')->end()
                    ->arrayNode('formats')
                        ->defaultValue(['xliff12'])
                        ->enumPrototype()->values(['xliff12'])->end()
                    ->end()
                    ->scalarNode('view')->defaultValue('@SfsCmsTranslationPlugin/admin/content/version_translations_import.html.twig')->end()
                    ->scalarNode('type')->defaultValue(VersionTranslationsImportForm::class)->end()
                    ->scalarNode('success_redirect_to')->defaultValue('')->end()
                ->end()
            );
        }
    }

    public function supports(string $modelClassName): bool
    {
        return Content::class === $modelClassName;
    }
}
