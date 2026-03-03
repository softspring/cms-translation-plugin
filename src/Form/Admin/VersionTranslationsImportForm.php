<?php

namespace Softspring\CmsTranslationPlugin\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class VersionTranslationsImportForm extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'sfs_cms_contents',
            'content_type' => null,
            'section' => null,
        ]);

        $resolver->setAllowedTypes('content_type', ['string', 'null']);

        $resolver->setNormalizer('label_format', function (Options $options, $value): string {
            return $options['content_type'] ? "admin_{$options['content_type']}.translations_import.%name%.label"
                : 'admin_sections.translations_import.%name%.label';
        });

        $resolver->setNormalizer('translation_domain', function (Options $options, $value): string {
            return $options['content_type'] ? 'sfs_cms_contents' : 'sfs_cms_admin';
        });
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('file', FileType::class, [
            'required' => true,
            'constraints' => new File(mimeTypes: 'text/xml'),
        ]);
    }
}
