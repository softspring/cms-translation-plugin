<?php

namespace Softspring\CmsTranslationPlugin\Tests\Unit\Admin\Routing;

use PHPUnit\Framework\TestCase;
use Softspring\CmsTranslationPlugin\Admin\Routing\ContentTypeRoutingProvider;

class ContentTypeRoutingProviderTest extends TestCase
{
    public function testItProvidesContentTranslationRoutes(): void
    {
        $provider = new ContentTypeRoutingProvider();

        self::assertSame(['sfs_cms_plugin_admin_content_type'], $provider->supportedTypes());
        self::assertTrue($provider->supports('sfs_cms_plugin_admin_content_type'));
        self::assertFalse($provider->supports('other'));

        $routes = $provider->getAdminRoutes('sfs_cms_plugin_admin_content_type');

        self::assertSame('/{content}/translations', $routes->get('translations')->getPath());
        self::assertSame('version_translations', $routes->get('translations')->getDefault('configKey'));
        self::assertSame('/{content}/translations/export/{target}.{format}', $routes->get('translations_export')->getPath());
        self::assertSame('/{content}/translations/export/{format}', $routes->get('translations_export_all')->getPath());
        self::assertSame('all', $routes->get('translations_export_all')->getDefault('target'));
        self::assertSame('/{content}/translations/import', $routes->get('translations_import')->getPath());
    }
}
