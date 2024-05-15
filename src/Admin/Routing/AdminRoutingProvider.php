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
        }

        return $collection;
    }
}
