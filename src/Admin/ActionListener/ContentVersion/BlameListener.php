<?php

namespace Softspring\CmsTranslationPlugin\Admin\ActionListener\ContentVersion;

use Softspring\CmsBundle\Admin\ActionListener\ContentVersion\BlameListener as BaseBlameListener;
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
