<?php

declare(strict_types=1);

namespace Softspring\CmsTranslationPlugin\EntityListener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Softspring\CmsBundle\Config\Exception\InvalidContentException;
use Softspring\CmsBundle\Model\VersionInterface;
use Softspring\CmsTranslationPlugin\Translator\ExtractException;
use Softspring\CmsTranslationPlugin\Translator\TranslatorExtractor;

class VersionTranslationStatisticsListener
{
    public function __construct(protected TranslatorExtractor $translatorExtractor)
    {
    }

    /**
     * @throws ExtractException
     * @throws InvalidContentException
     */
    public function prePersist(VersionInterface $version, PrePersistEventArgs $event): void
    {
        $statistics = $this->translatorExtractor->statistics($version);
        $version->setMetaField('translations_statistics', $statistics);
    }
}
