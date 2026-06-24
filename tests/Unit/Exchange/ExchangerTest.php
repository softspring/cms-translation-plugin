<?php

declare(strict_types=1);

namespace Softspring\CmsTranslationPlugin\Tests\Unit\Exchange;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Softspring\CmsTranslationPlugin\Exchange\Exchanger;
use Softspring\CmsTranslationPlugin\Exchange\ExchangerInterface;
use Softspring\CmsTranslationPlugin\Exchange\ImportResultCollection;
use Symfony\Component\HttpFoundation\File\File;

class ExchangerTest extends TestCase
{
    public function testItReturnsNamedExchangers(): void
    {
        $xliff = new SupportedImportExchanger();
        $exchanger = new Exchanger(['xliff12' => $xliff]);

        self::assertTrue($exchanger->has('xliff12'));
        self::assertFalse($exchanger->has('json'));
        self::assertSame($xliff, $exchanger->get('xliff12'));
    }

    public function testItRejectsUnknownExchangerNames(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exchanger "json" not found.');

        (new Exchanger([]))->get('json');
    }

    public function testItReturnsFirstImporterThatSupportsTheFile(): void
    {
        $unsupported = new UnsupportedImportExchanger();
        $supported = new SupportedImportExchanger();
        $exchanger = new Exchanger([$unsupported, $supported]);
        $file = new File(__FILE__);

        self::assertSame($supported, $exchanger->getImporter($file));
    }

    public function testItRejectsFilesWithoutImporter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No exchanger found for file');

        (new Exchanger([new UnsupportedImportExchanger()]))->getImporter(new File(__FILE__));
    }
}

class SupportedImportExchanger implements ExchangerInterface
{
    public static function format(): string
    {
        return 'supported';
    }

    public static function supportsImport(File $file): bool
    {
        return true;
    }

    public function importFile(File $file, array $flattenTranslations): ImportResultCollection
    {
        return new ImportResultCollection();
    }

    public function exportFile(array $flattenTranslations, string $domain, string $targetLocale, string $fallbackLocale, array $options = []): File
    {
        return new File(__FILE__);
    }
}

class UnsupportedImportExchanger extends SupportedImportExchanger
{
    public static function supportsImport(File $file): bool
    {
        return false;
    }
}
