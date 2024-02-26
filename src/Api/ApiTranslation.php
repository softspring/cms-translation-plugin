<?php

namespace Softspring\CmsTranslationPlugin\Api;

class ApiTranslation
{
    protected ?string $originText = null;
    protected ?string $translatedText = null;
    protected ?string $sourceLanguage = null;
    protected ?string $targetLanguage = null;
    protected array $options = [];
    protected bool $isTranslated = false;
    protected ?string $translationError = null;

    public static function create(string $originText, string $sourceLanguage = null, array $options = []): ApiTranslation
    {
        $translation = new self();
        $translation->setSourceLanguage($sourceLanguage);
        $translation->setOriginText($originText);
        $translation->setIsTranslated(false);
        $translation->setOptions($options);

        return $translation;
    }

    public function translate(string $targetLanguage, string $translatedText = null, string $translationError = null): self
    {
        $this->setTargetLanguage($targetLanguage);
        $this->setTranslatedText($translatedText);
        $this->setTranslationError($translationError);
        $this->setIsTranslated(true);

        return $this;
    }

    public function getOriginText(): ?string
    {
        return $this->originText;
    }

    public function setOriginText(?string $originText): void
    {
        $this->originText = $originText;
    }

    public function getTranslatedText(): ?string
    {
        return $this->translatedText;
    }

    public function setTranslatedText(?string $translatedText): void
    {
        $this->translatedText = $translatedText;
    }

    public function getSourceLanguage(): ?string
    {
        return $this->sourceLanguage;
    }

    public function setSourceLanguage(?string $sourceLanguage): void
    {
        $this->sourceLanguage = $sourceLanguage;
    }

    public function getTargetLanguage(): ?string
    {
        return $this->targetLanguage;
    }

    public function setTargetLanguage(?string $targetLanguage): void
    {
        $this->targetLanguage = $targetLanguage;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function isTranslated(): bool
    {
        return $this->isTranslated;
    }

    public function setIsTranslated(bool $isTranslated): void
    {
        $this->isTranslated = $isTranslated;
    }

    public function getTranslationError(): ?string
    {
        return $this->translationError;
    }

    public function setTranslationError(?string $translationError): void
    {
        $this->translationError = $translationError;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->isTranslated() ? 'success' : ($this->getTranslationError() ? 'error' : 'pending'),
            'data' => [
                'source' => $this->getSourceLanguage(),
                'target' => $this->getTargetLanguage(),
                'text' => $this->getOriginText(),
                'translation' => $this->getTranslatedText(),
                'options' => $this->getOptions(),
                'error' => $this->getTranslationError(),
            ],
        ];
    }
}
