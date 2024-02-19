<?php

namespace Softspring\CmsTranslationPlugin\Translator;

class TranslationsTransformer
{
    public static function flatten(array $translations): array
    {
        $flatten = [];

        foreach ($translations['_seo'] ?? [] as $field => $value) {
            $flatten["_seo:$field"] = $value;
        }

        foreach ($translations as $container => $modules) {
            if ('_seo' !== $container) {
                foreach ($modules as $i => $module) {
                    $flatten = array_merge($flatten, self::flattenModule("$container:$i", $module));
                }
            }
        }

        return $flatten;
    }

    protected static function flattenModule($prefix, array $module): array
    {
        $flatten = [
            "$prefix:_module" => $module['_module'],
        ];

        if ($module['modules'] ?? false) {
            foreach ($module['modules'] as $i => $submodule) {
                $flatten = array_merge($flatten, self::flattenModule("$prefix:modules:$i", $submodule));
            }
        }

        foreach ($module as $field => $value) {
            if (!in_array($field, ['_module', 'modules'])) {
                $flatten["$prefix:$field"] = $value;
            }
        }

        return $flatten;
    }

    public static function applySEO(array $seo, array $flattenTranslations): ?array
    {
        foreach ($flattenTranslations as $translationKey => $values) {
            if (str_starts_with($translationKey, '_seo')) {
                $seo[substr($translationKey, 5)] = $values;
            }
        }

        return $seo;
    }

    /**
     * @throws InvalidTranslationMappingException
     */
    public static function applyFlatten(?array $data, array $flattenTranslations): ?array
    {
        if (!$data) {
            return null;
        }

        foreach ($flattenTranslations as $translationKey => $values) {
            if (str_ends_with($translationKey, ':_module')) {
                continue;
            }
            if (str_starts_with($translationKey, '_seo')) {
                continue;
            }
            $translationKeyParts = explode(':', $translationKey);

            $current = &$data;
            foreach ($translationKeyParts as $part) {
                if (!isset($current[$part])) {
                    throw new InvalidTranslationMappingException();
                }
                $current = &$current[$part];
            }

            $current = $values;
        }

        return $data;
    }
}
