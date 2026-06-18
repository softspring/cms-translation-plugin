<?php

declare(strict_types=1);

namespace Softspring\CmsTranslationPlugin\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use Softspring\CmsTranslationPlugin\Utils\TranslationsCleaner;

class TranslationsCleanerTest extends TestCase
{
    public function testItNormalizesNullWhitespaceAndCarriageReturns(): void
    {
        self::assertSame('', TranslationsCleaner::cleanText(null));
        self::assertSame('line 1
line 2', TranslationsCleaner::cleanText("  line 1\r\nline 2  "));
    }
}
