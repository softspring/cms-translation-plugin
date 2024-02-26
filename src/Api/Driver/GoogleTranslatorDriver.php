<?php

namespace Softspring\CmsTranslationPlugin\Api\Driver;

use Google\Cloud\Core\Exception\ServiceException;
use Google\Cloud\Translate\V2\TranslateClient;
use Softspring\CmsTranslationPlugin\Api\ApiTranslation;

class GoogleTranslatorDriver implements TranslatorDriverInterface
{
    public function __construct(protected TranslateClient $client)
    {
    }

    public function translate(string $originText, string $targetLanguage, string $sourceLanguage = null, array $options = []): ApiTranslation
    {
        $googleOptions = [
            'format' => $options['format'] ?? 'text',
            'model' => $options['model'] ?? 'nmt',
            'target' => $targetLanguage,
        ];

        if ($sourceLanguage) {
            $googleOptions['source'] = $sourceLanguage;
        }

        $translation = ApiTranslation::create($originText, $sourceLanguage, $options);

        try {
            $result = $this->client->translate($originText, $googleOptions);
            $translation->translate($targetLanguage, $result['text'] ?? null);
        } catch (ServiceException $e) {
            $translation->translate($targetLanguage, null, $e->getMessage());
        }

        return $translation;
    }
}
