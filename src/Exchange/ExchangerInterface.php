<?php

declare(strict_types=1);

namespace Softspring\CmsTranslationPlugin\Exchange;

use Symfony\Component\HttpFoundation\File\File;

interface ExchangerInterface
{
    public static function format(): string;

    public static function supportsImport(File $file): bool;

    /**
     * @throws ImportException
     */
    public function importFile(File $file, array $flattenTranslations): ImportResultCollection;

    /**
     * @throws ExportException
     */
    public function exportFile(array $flattenTranslations, string $domain, string $targetLocale, string $fallbackLocale, array $options = []): File;
}
