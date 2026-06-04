<?php

declare(strict_types=1);

namespace Softspring\CmsTranslationPlugin\Exchange;

class ImportResultCollection
{
    protected array $results = [];

    public function addResult(ImportResult $result): void
    {
        $this->results[] = $result;
    }

    /**
     * @return ImportResult[]
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
