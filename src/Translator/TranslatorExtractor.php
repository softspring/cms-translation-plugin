<?php

namespace Softspring\CmsTranslationPlugin\Translator;

use Softspring\CmsBundle\Config\CmsConfig;
use Softspring\CmsBundle\Config\Exception\InvalidContentException;
use Softspring\CmsBundle\Form\Module\ContainerModuleType;
use Softspring\CmsBundle\Manager\ContentManagerInterface;
use Softspring\CmsBundle\Model\ContentVersionInterface;

class TranslatorExtractor
{
    public function __construct(
        protected CmsConfig $cmsConfig,
        protected ContentManagerInterface $contentManager
    ) {
    }

    /**
     * @throws ExtractException
     * @throws InvalidContentException
     */
    public function extract(ContentVersionInterface $contentVersion): array
    {
        $translations = [];

        $contentConfig = $this->cmsConfig->getContent($this->contentManager->getType($contentVersion->getContent()))['version_seo'];
        $seo = $contentVersion->getSeo();
        foreach ($contentConfig as $field => $fieldConfig) {
            $translations['_seo'][$field] = $this->extractFieldTranslations($fieldConfig, $seo[$field] ?? null);
        }

        $data = $contentVersion->getData();
        foreach ($data ?? [] as $container => $modules) {
            $translations[$container] = $this->extractContainer($modules);
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

            $moduleTypeReflection = new \ReflectionClass($moduleConfig['module_type']);
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
                    $translations[$field] = $this->extractFieldTranslations($fieldConfig, $moduleData[$field]);
                }
            }

            return array_filter($translations);
        } catch (\Exception $e) {
            throw new ExtractException('Error extracting translations', 0, $e);
        }
    }

    protected function extractFieldTranslations(array $fieldConfig, mixed $fieldValue): ?array
    {
        if ('translatable' !== $fieldConfig['type']) {
            return null;
        }

        if (!in_array($fieldConfig['type_options']['type'] ?? 'text', ['text', 'textarea', 'html'])) {
            return null;
        }

        if (!is_array($fieldValue)) {
            return null;
        }

        return $fieldValue;
    }
}
