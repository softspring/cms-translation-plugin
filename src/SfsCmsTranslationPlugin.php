<?php

namespace Softspring\CmsTranslationPlugin;

use Softspring\CmsBundle\DependencyInjection\Compiler\AddTwigBundlesNamespacesPass;
use Softspring\CmsBundle\Plugin\SfsCmsPlugin;
use Softspring\CmsTranslationPlugin\Config\Model\ContentExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SfsCmsTranslationPlugin extends SfsCmsPlugin
{
    public const ADMIN_CONTENT_VERSIONS_TRANSLATIONS_INITIALIZE = 'sfs_cms_translation_plugin.admin.content_versions.translations_initialize';
    public const ADMIN_CONTENT_VERSIONS_TRANSLATIONS_ENTITY = 'sfs_cms_translation_plugin.admin.content_versions.translations_create_entity';
    public const ADMIN_CONTENT_VERSIONS_TRANSLATIONS_FORM_PREPARE = 'sfs_cms_translation_plugin.admin.content_versions.translations_form_prepare';
    public const ADMIN_CONTENT_VERSIONS_TRANSLATIONS_FORM_INIT = 'sfs_cms_translation_plugin.admin.content_versions.translations_form_init';
    public const ADMIN_CONTENT_VERSIONS_TRANSLATIONS_FORM_VALID = 'sfs_cms_translation_plugin.admin.content_versions.translations_form_valid';
    public const ADMIN_CONTENT_VERSIONS_TRANSLATIONS_APPLY = 'sfs_cms_translation_plugin.admin.content_versions.translations_apply';
    public const ADMIN_CONTENT_VERSIONS_TRANSLATIONS_SUCCESS = 'sfs_cms_translation_plugin.admin.content_versions.translations_success';
    public const ADMIN_CONTENT_VERSIONS_TRANSLATIONS_FAILURE = 'sfs_cms_translation_plugin.admin.content_versions.translations_failure';
    public const ADMIN_CONTENT_VERSIONS_TRANSLATIONS_FORM_INVALID = 'sfs_cms_translation_plugin.admin.content_versions.translations_form_invalid';
    public const ADMIN_CONTENT_VERSIONS_TRANSLATIONS_VIEW = 'sfs_cms_translation_plugin.admin.content_versions.translations_view';
    public const ADMIN_CONTENT_VERSIONS_TRANSLATIONS_EXCEPTION = 'sfs_cms_translation_plugin.admin.content_versions.translations_exception';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // allow override bundles templates
        $container->addCompilerPass(new AddTwigBundlesNamespacesPass($this->getPath().'/templates'));
    }

    protected function getConfigExtensionClasses(): array
    {
        return [
            ContentExtension::class,
        ];
    }
}
