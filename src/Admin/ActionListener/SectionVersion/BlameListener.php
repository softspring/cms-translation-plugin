<?php

declare(strict_types=1);

namespace Softspring\CmsTranslationPlugin\Admin\ActionListener\SectionVersion;

use Softspring\CmsSectionsPlugin\Admin\ActionListener\SectionVersion\BlameListener as BaseBlameListener;
use Softspring\CmsTranslationPlugin\SfsCmsTranslationPlugin;

class BlameListener extends BaseBlameListener
{
    public static function getSubscribedEvents(): array
    {
        return [
            SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_APPLY => [
                ['onCreateVersion', 5],
            ],
        ];
    }
}
