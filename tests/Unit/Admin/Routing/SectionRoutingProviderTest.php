<?php

namespace Softspring\CmsTranslationPlugin\Tests\Unit\Admin\Routing;

use PHPUnit\Framework\TestCase;
use Softspring\CmsTranslationPlugin\Admin\Routing\SectionRoutingProvider;

class SectionRoutingProviderTest extends TestCase
{
    public function testItProvidesSectionTranslationRoutes(): void
    {
        $provider = new SectionRoutingProvider();

        self::assertSame(['sfs_cms_plugin_admin_section'], $provider->supportedTypes());
        self::assertTrue($provider->supports('sfs_cms_plugin_admin_section'));
        self::assertFalse($provider->supports('other'));

        $routes = $provider->getAdminRoutes('sfs_cms_plugin_admin_section');

        self::assertSame('/{section}/translations', $routes->get('translations')->getPath());
        self::assertSame('version_translations', $routes->get('translations')->getDefault('configKey'));
        self::assertSame('/{section}/translations/export/{target}.{format}', $routes->get('translations_export')->getPath());
        self::assertSame('/{section}/translations/export/{format}', $routes->get('translations_export_all')->getPath());
        self::assertSame('all', $routes->get('translations_export_all')->getDefault('target'));
        self::assertSame('/{section}/translations/import', $routes->get('translations_import')->getPath());
    }
}
