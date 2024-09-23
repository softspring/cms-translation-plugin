<?php

namespace Softspring\CmsTranslationPlugin\Exchange;

use Symfony\Component\HttpFoundation\File\File;

interface ExchangerInterface
{
    public static function format(): string;

    //    public function import();

    /**
     * @throws ExportException
     */
    public function exportFile(array $flattenTranslations, string $domain, string $targetLocale, string $fallbackLocale, array $options = []): File;
}
