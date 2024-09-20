<?php

namespace Softspring\CmsTranslationPlugin\Translator;

use Softspring\CmsBundle\Model\ContentVersionInterface;
use Symfony\Component\Translation\MessageCatalogue;

class ContentMessageCatalogue extends MessageCatalogue
{
    public function __construct(string $locale, ContentVersionInterface $version)
    {
        $messages = [
            $version->getContent()->getId() => [
                $locale => [

                ],
            ],
        ];

        parent::__construct($locale, $messages);
    }
}
