<?php

declare(strict_types=1);

namespace Softspring\CmsTranslationPlugin\Tests\Unit\Exchange;

use PHPUnit\Framework\TestCase;
use Softspring\CmsTranslationPlugin\Exchange\ImportResult;

class ImportResultTest extends TestCase
{
    public function testItStoresImportMetadataTranslationsAndWarnings(): void
    {
        $result = new ImportResult(['title' => ['en' => 'Title']], 'App\Entity\Page', 'page-1', '7');

        $result->setSourceLanguage('en');
        $result->setTargetLanguage('es');
        $result->setDomain('page-1_v7');
        $result->setFlattenTranslations(['title' => ['en' => 'Title', 'es' => 'Titulo']]);
        $result->addGlobalWarning('Global warning');
        $result->addFieldWarning('title', 'es', 'Field warning');

        self::assertSame('en', $result->getSourceLanguage());
        self::assertSame('es', $result->getTargetLanguage());
        self::assertSame('page-1_v7', $result->getDomain());
        self::assertSame(['title' => ['en' => 'Title', 'es' => 'Titulo']], $result->getFlattenTranslations());
        self::assertSame(['title' => ['en' => 'Title']], $result->getOriginalFlattenTranslations());
        self::assertSame('App\Entity\Page', $result->getEntityClass());
        self::assertSame('page-1', $result->getEntityId());
        self::assertSame('7', $result->getVersionNumber());
        self::assertTrue($result->hasGlobalWarnings());
        self::assertSame(['Global warning'], $result->getGlobalWarnings());
        self::assertTrue($result->hasFieldWarnings());
        self::assertTrue($result->hasFieldWarnings('title'));
        self::assertSame(['es' => ['Field warning']], $result->getFieldWarnings('title'));
        self::assertSame(['title' => ['es' => ['Field warning']]], $result->getFieldWarnings());
        self::assertSame([], $result->getChangeLog());
    }

    public function testItAllowsChangingReferenceData(): void
    {
        $result = new ImportResult([], 'old-class', 'old-id', '1');

        $result->setOriginalFlattenTranslations(['headline' => ['en' => 'Headline']]);
        $result->setEntityClass('new-class');
        $result->setEntityId('new-id');
        $result->setVersionNumber('2');

        self::assertSame(['headline' => ['en' => 'Headline']], $result->getOriginalFlattenTranslations());
        self::assertSame('new-class', $result->getEntityClass());
        self::assertSame('new-id', $result->getEntityId());
        self::assertSame('2', $result->getVersionNumber());
        self::assertFalse($result->hasGlobalWarnings());
        self::assertFalse($result->hasFieldWarnings());
    }
}
