<?php

namespace Softspring\CmsTranslationPlugin\Form\Extension;

use Softspring\CmsBundle\Config\CmsConfig;
use Softspring\CmsBundle\Form\Type\TranslatableType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\RouterInterface;

class TranslatableExtension extends AbstractTypeExtension
{
    public function __construct(
        protected CmsConfig $cmsConfig,
        protected RouterInterface $router,
        protected bool $apiEnabled,
        protected ?string $apiDriver = null,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [TranslatableType::class];
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$this->apiDriver) {
            return;
        }

        foreach ($view->children as $locale => $field) {
            if (str_starts_with($locale, '_')) {
                continue;
            }

            $fallback = $view->children['_default']->vars['value'] ?? 'en';

            if ($locale === $fallback) {
                continue;
            }

            $fallbackField = $view->children[$fallback] ?? null;

            $field->vars['translate_prepend_button'] = [
                'attr' => [
                    'data-translate' => '',
                    'data-translate-source-field' => $fallbackField->vars['full_name'] ?? '',
                    'data-translate-target-field' => $field->vars['full_name'],
                    'data-translate-source-locale' => $fallback,
                    'data-translate-target-locale' => $locale,
                    'data-translate-url' => $this->router->generate('sfs_cms_translation_api_translate'),
                ],
                'api_driver' => $this->apiDriver,
                'enabled' => null !== $fallbackField,
            ];

            $field->vars['block_prefixes'][] = 'translatable_element_with_api';
        }
    }
}
