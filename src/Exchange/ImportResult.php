<?php

namespace Softspring\CmsTranslationPlugin\Exchange;

class ImportResult
{
    protected string $sourceLanguage;

    protected string $targetLanguage;

    protected array $flattenTranslations;

    protected string $domain;

    protected array $warnings = [];

    public function __construct(
        protected array $originalFlattenTranslations,
        protected string $entityClass,
        protected string $entityId,
        protected string $versionNumber,
    ) {
    }

    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    public function setSourceLanguage(string $sourceLanguage): void
    {
        $this->sourceLanguage = $sourceLanguage;
    }

    public function getTargetLanguage(): string
    {
        return $this->targetLanguage;
    }

    public function setTargetLanguage(string $targetLanguage): void
    {
        $this->targetLanguage = $targetLanguage;
    }

    public function setFlattenTranslations(array $flattenTranslations): void
    {
        $this->flattenTranslations = $flattenTranslations;
    }

    public function getFlattenTranslations(): array
    {
        return $this->flattenTranslations;
    }

    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function addWarning(string $message): void
    {
        $this->warnings[] = $message;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function hasWarnings(): bool
    {
        return count($this->warnings) > 0;
    }

    public function getOriginalFlattenTranslations(): array
    {
        return $this->originalFlattenTranslations;
    }

    public function setOriginalFlattenTranslations(array $originalFlattenTranslations): void
    {
        $this->originalFlattenTranslations = $originalFlattenTranslations;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass): void
    {
        $this->entityClass = $entityClass;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function setEntityId(string $entityId): void
    {
        $this->entityId = $entityId;
    }

    public function getVersionNumber(): string
    {
        return $this->versionNumber;
    }

    public function setVersionNumber(string $versionNumber): void
    {
        $this->versionNumber = $versionNumber;
    }

    public function getChangeLog(): array
    {
        // TODO: Implement getChangeLog() method.
        return [];
    }
}
