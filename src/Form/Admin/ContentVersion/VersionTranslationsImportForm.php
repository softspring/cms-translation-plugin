<?php

namespace Softspring\CmsTranslationPlugin\Form\Admin\ContentVersion;

use Softspring\CmsBundle\Model\ContentInterface;
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
            'content_config' => null,
        ]);

        $resolver->setRequired('content_type');
        $resolver->setAllowedTypes('content_type', ['string']);

        $resolver->setRequired('content');
        $resolver->setAllowedTypes('content', [ContentInterface::class]);

        $resolver->setNormalizer('label_format', function (Options $options, $value) {
            return "admin_{$options['content_type']}.translations_import.%name%.label";
        });
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('file', FileType::class, [
            'required' => true,
            'constraints' => new File([
                'mimeTypes' => 'text/xml',
            ]),
        ]);
    }
}
