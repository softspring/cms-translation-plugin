<?php

namespace Softspring\CmsTranslationPlugin\Tests\Unit\Exchange;

use PHPUnit\Framework\TestCase;
use Softspring\CmsTranslationPlugin\Exchange\ImportResult;
use Softspring\CmsTranslationPlugin\Exchange\ImportResultCollection;

class ImportResultCollectionTest extends TestCase
{
    public function testItCollectsResultsInInsertionOrder(): void
    {
        $first = new ImportResult([], 'class', 'first', '1');
        $second = new ImportResult([], 'class', 'second', '2');
        $collection = new ImportResultCollection();

        self::assertSame([], $collection->getResults());

        $collection->addResult($first);
        $collection->addResult($second);

        self::assertSame([$first, $second], $collection->getResults());
    }
}
