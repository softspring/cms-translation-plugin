<?php

namespace Softspring\CmsTranslationPlugin\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Softspring\CmsTranslationPlugin\SfsCmsTranslationPlugin;

class SfsCmsTranslationPluginTest extends TestCase
{
    public function testItExposesAliasAndPath(): void
    {
        $plugin = new SfsCmsTranslationPlugin();

        self::assertSame('sfs_cms_translation', SfsCmsTranslationPlugin::getAlias());
        self::assertSame(dirname(__DIR__, 2), $plugin->getPath());
    }
}
