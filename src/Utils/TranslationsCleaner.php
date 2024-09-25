<?php

namespace Softspring\CmsTranslationPlugin\Utils;

class TranslationsCleaner
{
    public static function cleanText(?string $text): string
    {
        $text = "$text";
        $text = str_replace("\r", '', $text);

        return trim($text);
    }
}
