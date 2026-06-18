<?php

namespace Softspring\CmsTranslationPlugin\Tests\Unit\Form\Admin;

use PHPUnit\Framework\TestCase;
use Softspring\CmsTranslationPlugin\Form\Admin\VersionTranslationsImportForm;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class VersionTranslationsImportFormTest extends TestCase
{
    public function testItConfiguresContentImportOptions(): void
    {
        $resolver = new OptionsResolver();
        $form = new VersionTranslationsImportForm();

        $form->configureOptions($resolver);

        $options = $resolver->resolve(['content_type' => 'page']);

        self::assertSame('sfs_cms_contents', $options['translation_domain']);
        self::assertSame('admin_page.translations_import.%name%.label', $options['label_format']);
    }

    public function testItConfiguresSectionImportOptions(): void
    {
        $resolver = new OptionsResolver();
        $form = new VersionTranslationsImportForm();

        $form->configureOptions($resolver);

        $options = $resolver->resolve();

        self::assertSame('sfs_cms_admin', $options['translation_domain']);
        self::assertSame('admin_sections.translations_import.%name%.label', $options['label_format']);
    }

    public function testItBuildsFileField(): void
    {
        $form = new VersionTranslationsImportForm();
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->once())
            ->method('add')
            ->with(
                'file',
                FileType::class,
                self::callback(static function (array $options): bool {
                    return true === $options['required'] && $options['constraints'] instanceof File;
                }),
            )
            ->willReturnSelf();

        $form->buildForm($builder, []);
    }
}
