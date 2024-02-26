<?php

namespace Softspring\CmsTranslationPlugin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class TranslatorExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(protected ?string $apiDriver = null)
    {
    }

    public function getGlobals(): array
    {
        return [
            'sfs_cms_translation' => [
                'api' => [
                    'driver' => $this->apiDriver,
                ],
            ],
        ];
    }
}
