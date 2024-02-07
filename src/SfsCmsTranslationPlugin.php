<?php

namespace Softspring\CmsTranslationPlugin;

use Softspring\CmsBundle\Plugin\SfsCmsPlugin;

class SfsCmsTranslationPlugin extends SfsCmsPlugin
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
