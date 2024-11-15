<?php

namespace Softspring\CmsTranslationPlugin\EntityListener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Softspring\CmsBundle\Config\Exception\InvalidContentException;
use Softspring\CmsBundle\Model\ContentVersionInterface;
use Softspring\CmsTranslationPlugin\Translator\ExtractException;
use Softspring\CmsTranslationPlugin\Translator\TranslatorExtractor;

class ContentVersionTranslationStatisticsListener
{
    public function __construct(protected TranslatorExtractor $translatorExtractor)
    {
    }

    /**
     * @throws ExtractException
     * @throws InvalidContentException
     */
    public function prePersist(ContentVersionInterface $contentVersion, PrePersistEventArgs $event): void
    {
        $statistics = $this->translatorExtractor->statistics($contentVersion);
        $contentVersion->setMetaField('translations_statistics', $statistics);
    }
}