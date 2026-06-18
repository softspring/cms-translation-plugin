<?php

namespace Softspring\CmsTranslationPlugin\Tests\Unit\Form\Admin;

use PHPUnit\Framework\TestCase;
use Softspring\CmsBundle\Config\CmsConfig;
use Softspring\CmsBundle\Form\Type\TranslationType;
use Softspring\CmsTranslationPlugin\Form\Admin\VersionTranslateForm;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VersionTranslateFormTest extends TestCase
{
    public function testItConfiguresOptions(): void
    {
        $resolver = new OptionsResolver();
        $form = new VersionTranslateForm($this->createMock(CmsConfig::class));

        $form->configureOptions($resolver);

        $options = $resolver->resolve(['flatten_translations' => []]);

        self::assertSame([], $options['flatten_translations']);
        self::assertNull($options['flatten_translations_before_importing']);
    }

    public function testItBuildsTranslationFieldsFromFlattenTranslations(): void
    {
        $cmsConfig = $this->createMock(CmsConfig::class);
        $cmsConfig->expects($this->once())
            ->method('getModule')
            ->with('text')
            ->willReturn([
                'module_options' => [
                    'form_fields' => [
                        'body' => [
                            'type_options' => [
                                'type' => 'textarea',
                            ],
                        ],
                    ],
                ],
            ]);

        $calls = [];
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')
            ->willReturnCallback(function (string $name, ?string $type = null, array $options = []) use (&$calls, $builder): FormBuilderInterface {
                $calls[$name] = [$type, $options];

                return $builder;
            });

        $form = new VersionTranslateForm($cmsConfig);
        $form->buildForm($builder, [
            'flatten_translations' => [
                'main:0:_module' => 'text',
                'main:0:body' => ['en' => 'Body'],
                '_seo:title' => ['en' => 'SEO title'],
            ],
        ]);

        self::assertArrayNotHasKey('main:0:_module', $calls);
        self::assertSame(TranslationType::class, $calls['main:0:body'][0]);
        self::assertSame('textarea', $calls['main:0:body'][1]['type']);
        self::assertSame('main:0', $calls['main:0:body'][1]['attr']['data-module']);
        self::assertSame('text', $calls['main:0:body'][1]['attr']['data-module-type']);
        self::assertSame('body', $calls['main:0:body'][1]['attr']['data-module-field']);
        self::assertSame('height:300px', $calls['main:0:body'][1]['children_attr']['style']);
        self::assertSame(TranslationType::class, $calls['_seo:title'][0]);
        self::assertSame('admin_page.form.seo.%name%.label', $calls['_seo:title'][1]['label_format']);
    }
}
