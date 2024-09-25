<?php

namespace Softspring\CmsTranslationPlugin\Admin\Routing;

use Softspring\CmsBundle\Admin\Routing\AdminRoutingProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class AdminRoutingProvider implements AdminRoutingProviderInterface
{
    public function __construct(protected bool $apiEnabled)
    {
    }

    public function getAdminRoutes(string $type): RouteCollection
    {
        $collection = new RouteCollection();

        if ('sfs_cms_plugin_admin_content_type' === $type) {
            $collection->add('translations', new Route('/{content}/translations', [
                '_controller' => 'sfs_cms.translation_plugin.admin.content_version.controller::create',
                'configKey' => 'version_translations',
            ]));

            if ($this->apiEnabled) {
                $collection->add('api_translate', new Route('/{content}/api/translate', [
                    '_controller' => 'Softspring\CmsTranslationPlugin\Controller\TranslatorController::translate',
                ]));
            }

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
        }

        return $collection;
    }
}
