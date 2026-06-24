<?php

declare(strict_types=1);

namespace Softspring\CmsTranslationPlugin\Tests\Unit\EntityListener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Softspring\CmsBundle\Model\VersionInterface;
use Softspring\CmsTranslationPlugin\EntityListener\VersionTranslationStatisticsListener;
use Softspring\CmsTranslationPlugin\Translator\TranslatorExtractor;

class VersionTranslationStatisticsListenerTest extends TestCase
{
    public function testItStoresTranslationStatisticsOnVersionMeta(): void
    {
        $statistics = [
            'default_locale' => 'en',
            'total' => 3,
            'locales' => ['es' => ['translated' => 2, 'percentage' => 66.67]],
        ];
        $version = $this->createMock(VersionInterface::class);
        $version->expects(self::once())->method('setMetaField')->with('translations_statistics', $statistics);

        $extractor = $this->createStub(TranslatorExtractor::class);
        $extractor->method('statistics')->with($version)->willReturn($statistics);

        $event = new PrePersistEventArgs($version, $this->createStub(EntityManagerInterface::class));

        (new VersionTranslationStatisticsListener($extractor))->prePersist($version, $event);
    }
}
