<?php

namespace Softspring\CmsTranslationPlugin\Admin\Routing;

use Softspring\CmsBundle\Routing\Provider\RoutingProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ContentTypeRoutingProvider implements RoutingProviderInterface
{
    public function supportedTypes(): array
    {
        return [
            'sfs_cms_plugin_admin_content_type',
        ];
    }

    public function supports(string $type): bool
    {
        return in_array($type, $this->supportedTypes());
    }

    public function getAdminRoutes(string $type): RouteCollection
    {
        $collection = new RouteCollection();

        $collection->add('translations', new Route('/{content}/translations', [
            '_controller' => 'sfs_cms.translation_plugin.admin.content_version.controller::create',
            'configKey' => 'version_translations',
        ]));

        $collection->add('translations_export', new Route('/{content}/translations/export/{target}.{format}', [
            '_controller' => 'sfs_cms.translation_plugin.admin.content_version.controller::apply',
            'configKey' => 'version_translations_export',
        ]));

        $collection->add('translations_export_all', new Route('/{content}/translations/export/{format}', [
            '_controller' => 'sfs_cms.translation_plugin.admin.content_version.controller::apply',
            'configKey' => 'version_translations_export',
            'target' => 'all',
        ]));

        $collection->add('translations_import', new Route('/{content}/translations/import', [
            '_controller' => 'sfs_cms.translation_plugin.admin.content_version.controller::create',
            'configKey' => 'version_translations_import',
        ]));

        return $collection;
    }
}
