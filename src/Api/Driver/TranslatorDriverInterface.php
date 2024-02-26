<?php

namespace Softspring\CmsTranslationPlugin\Api\Driver;

use Softspring\CmsTranslationPlugin\Api\ApiTranslation;

interface TranslatorDriverInterface
{
    public function translate(string $originText, string $targetLanguage, string $sourceLanguage = null, array $options = []): ApiTranslation;
}
