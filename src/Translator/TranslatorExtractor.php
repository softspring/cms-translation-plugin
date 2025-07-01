<?php

namespace Softspring\CmsTranslationPlugin\Translator;

use Exception;
use ReflectionClass;
use Softspring\CmsBundle\Config\CmsConfig;
use Softspring\CmsBundle\Config\Exception\InvalidContentException;
use Softspring\CmsBundle\Form\Module\ContainerModuleType;
use Softspring\CmsBundle\Manager\ContentManagerInterface;
use Softspring\CmsBundle\Manager\SectionManagerInterface;
use Softspring\CmsBundle\Model\ContentDataInterface;
use Softspring\CmsBundle\Model\ContentVersionInterface;
use Softspring\CmsBundle\Model\TranslatableConfigInterface;
use Softspring\CmsBundle\Model\VersionableInterface;
use Softspring\CmsBundle\Model\VersionInterface;
use Softspring\TranslatableBundle\Model\Translation;

class TranslatorExtractor
{
    public function __construct(
        protected CmsConfig $cmsConfig,
        protected ContentManagerInterface $contentManager,
        protected SectionManagerInterface $sectionManager,
    ) {
    }

    /**
     * @throws InvalidContentException
     * @throws ExtractException
     */
    public function statistics(VersionInterface $version): array
    {
        $translations = $this->extract($version);
        $flatten = TranslationsTransformer::flatten($translations);

        /** @var VersionableInterface|TranslatableConfigInterface $parent */
        $parent = $version->getParent();

        $defaultLocale = $parent->getDefaultLocale();
        $translationsByLocale = [];
        $total = 0;
        foreach ($flatten as $translation) {
            if ($translation instanceof Translation) {
                if (!$translation[$defaultLocale]) {
                    continue;
                }
                ++$total;
                foreach ($translation->getTranslations() as $locale => $value) {
                    if (!isset($translationsByLocale[$locale])) {
                        $translationsByLocale[$locale] = 0;
                    }
                    if (!empty($translation[$locale])) {
                        ++$translationsByLocale[$locale];
                    }
                }
            }
        }

        $statistics = [
            'default_locale' => $defaultLocale,
            'total' => $total,
            'locales' => [],
        ];

        foreach ($translationsByLocale as $locale => $translated) {
            $statistics['locales'][$locale] = [
                'translated' => $translated,
                'percentage' => round($translated / $total * 100, 2),
            ];
        }

        return $statistics;
    }

    /**
     * @throws ExtractException
     * @throws InvalidContentException
     */
    public function extract(VersionInterface $version): array
    {
        if (!$version instanceof ContentDataInterface) {
            throw new ExtractException('Invalid version type. Expected ContentDataInterface.');
        }

        $translations = [];

        if ($version instanceof ContentVersionInterface) {
            $contentConfig = $this->cmsConfig->getContent($this->contentManager->getType($version->getContent()))['version_seo'];
            $seo = $version->getSeo();
            foreach ($contentConfig as $field => $fieldConfig) {
                $translations['_seo'][$field] = $this->extractFieldTranslations($fieldConfig, $seo[$field] ?? null);
            }

            $data = $version->getData();
            foreach ($data ?? [] as $container => $modules) {
                $translations[$container] = $this->extractContainer($modules);
            }
        } else {
            $data = $version->getData() ?? [];
            $translations['_section'] = $this->extractContainer($data);
        }

        return $translations;
    }

    /**
     * @throws ExtractException
     */
    protected function extractContainer(array $modules): array
    {
        $translations = [];

        foreach ($modules as $module => $fields) {
            $translations[$module] = $this->extractModule($fields);
        }

        return $translations;
    }

    /**
     * @throws ExtractException
     */
    protected function extractModule(array $moduleData): array
    {
        try {
            $translations = [];

            $moduleConfig = $this->cmsConfig->getModule($moduleData['_module']);

            $moduleTypeReflection = new ReflectionClass($moduleConfig['module_type']);
            $isContainer = ContainerModuleType::class === $moduleConfig['module_type'] || $moduleTypeReflection->isSubclassOf(ContainerModuleType::class);

            $translations['_module'] = $moduleData['_module'];

            if ($isContainer) {
                foreach ($moduleData['modules'] as $module => $fields) {
                    $translations['modules'][$module] = $this->extractModule($fields);
                }
            } else {
                foreach ($moduleConfig['module_options']['form_fields'] ?? [] as $field => $fieldConfig) {
                    if (null === $fieldConfig) {
                        continue;
                    }
                    $translations[$field] = $this->extractFieldTranslations($fieldConfig, $moduleData[$field] ?? null);
                }
            }

            return array_filter($translations);
        } catch (Exception $e) {
            throw new ExtractException('Error extracting translations', 0, $e);
        }
    }

    protected function extractFieldTranslations(array $fieldConfig, mixed $fieldValue): ?Translation
    {
        if ('translation' !== $fieldConfig['type']) {
            return null;
        }

        if (!in_array($fieldConfig['type_options']['type'] ?? 'text', ['text', 'textarea', 'html'])) {
            return null;
        }

        if (!$fieldValue instanceof Translation) {
            return null;
        }

        return $fieldValue;
    }
}
