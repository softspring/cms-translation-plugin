<?php

declare(strict_types=1);

namespace Softspring\CmsTranslationPlugin\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Softspring\CmsTranslationPlugin\DependencyInjection\Configuration;
use Softspring\CmsTranslationPlugin\DependencyInjection\SfsCmsTranslationExtension;
use Softspring\CmsTranslationPlugin\SfsCmsTranslationPlugin;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SfsCmsTranslationExtensionTest extends TestCase
{
    public function testConfigurationUsesPluginAlias(): void
    {
        $tree = (new Configuration())->getConfigTreeBuilder()->buildTree();

        self::assertSame(SfsCmsTranslationPlugin::getAlias(), $tree->getName());
    }

    public function testItLoadsBaseServices(): void
    {
        $container = new ContainerBuilder();

        (new SfsCmsTranslationExtension())->load([], $container);

        self::assertTrue($container->hasDefinition('Softspring\CmsTranslationPlugin\Exchange\Exchanger'));
        self::assertTrue($container->hasDefinition('Softspring\CmsTranslationPlugin\Translator\TranslatorExtractor'));
    }

    public function testItLoadsSectionServicesWhenSectionsPluginIsRegistered(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('sfs_cms.registered_plugins', [
            ['alias' => 'sfs_cms_sections'],
        ]);

        (new SfsCmsTranslationExtension())->load([], $container);

        self::assertTrue($container->hasDefinition('Softspring\CmsTranslationPlugin\Admin\ActionListener\SectionVersion\TranslationsListener'));
    }

    public function testItPrependsAssetMapperPathWhenAssetMapperIsAvailable(): void
    {
        $container = new ContainerBuilder();

        (new SfsCmsTranslationExtension())->prepend($container);

        if (!interface_exists(AssetMapperInterface::class)) {
            self::assertSame([], $container->getExtensionConfig('framework'));

            return;
        }

        $frameworkConfig = $container->getExtensionConfig('framework')[0];
        self::assertContains('@softspring/cms-translation-plugin', $frameworkConfig['asset_mapper']['paths']);
    }
}
