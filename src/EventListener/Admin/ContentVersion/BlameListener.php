<?php

namespace Softspring\CmsTranslationPlugin\EventListener\Admin\ContentVersion;

use Softspring\CmsBundle\EventListener\Admin\ContentVersion\BlameListener as BaseBlameListener;
use Softspring\CmsTranslationPlugin\SfsCmsTranslationPlugin;

class BlameListener extends BaseBlameListener
{
    public static function getSubscribedEvents(): array
    {
        return [
            SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_APPLY => [
                ['onCreateVersion', 5],
            ],
        ];
    }
}
