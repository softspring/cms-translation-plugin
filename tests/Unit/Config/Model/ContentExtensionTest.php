<?php

declare(strict_types=1);

namespace Softspring\CmsTranslationPlugin\Tests\Unit\Config\Model;

use PHPUnit\Framework\TestCase;
use Softspring\CmsBundle\Config\Model\Content;
use Softspring\CmsTranslationPlugin\Config\Model\ContentExtension;
use Softspring\CmsTranslationPlugin\Form\Admin\VersionTranslateForm;
use Softspring\CmsTranslationPlugin\Form\Admin\VersionTranslationsImportForm;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ContentExtensionTest extends TestCase
{
    public function testItSupportsCmsContentConfiguration(): void
    {
        $extension = new ContentExtension();

        self::assertTrue($extension->supports(Content::class));
        self::assertFalse($extension->supports(self::class));
    }

    public function testItAddsTranslationAdminDefaults(): void
    {
        $treeBuilder = new TreeBuilder('content');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('admin')
                    ->addDefaultsIfNotSet()
                    ->children()
                    ->end()
                ->end()
            ->end()
        ;

        (new ContentExtension())->extend($rootNode);

        $config = (new Processor())->process($treeBuilder->buildTree(), [[]]);

        self::assertSame('PERMISSION_SFS_CMS_ADMIN_CONTENT_TRANSLATIONS', $config['admin']['version_translations']['is_granted']);
        self::assertSame(VersionTranslateForm::class, $config['admin']['version_translations']['type']);
        self::assertSame(['xliff12'], $config['admin']['version_translations_export']['formats']);
        self::assertSame('PERMISSION_SFS_CMS_ADMIN_CONTENT_TRANSLATIONS_IMPORT', $config['admin']['version_translations_import']['is_granted']);
        self::assertSame(VersionTranslationsImportForm::class, $config['admin']['version_translations_import']['type']);
    }
}
