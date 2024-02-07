<?php

namespace Softspring\CmsTranslationPlugin\Config\Model;

use Softspring\CmsBundle\Config\Model\ConfigExtensionInterface;
use Softspring\CmsBundle\Config\Model\Content;
use Softspring\CmsTranslationPlugin\Form\Admin\ContentVersion\VersionTranslateForm;
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

            $versionTranslations = (new ArrayNodeDefinition('version_translations'))
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('is_granted')->defaultValue('PERMISSION_SFS_CMS_ADMIN_CONTENT_TRANSLATIONS')->end()
                    ->scalarNode('view')->defaultValue('@SfsCmsTranslationPlugin/admin/content/version_translations.html.twig')->end()
                    ->scalarNode('type')->defaultValue(VersionTranslateForm::class)->end()
                    ->scalarNode('success_redirect_to')->defaultValue('')->end()
                ->end()
            ;

            $node->append($versionTranslations);
        }
    }

    public function supports(string $modelClassName): bool
    {
        return Content::class === $modelClassName;
    }
}
