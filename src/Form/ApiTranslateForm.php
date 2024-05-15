<?php

namespace Softspring\CmsTranslationPlugin\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApiTranslateForm extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return '';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'block_prefix' => '',
            'block_name' => '',
            'method' => 'GET',
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('text', TextType::class);
        $builder->add('source', TextType::class);
        $builder->add('target', TextType::class);
    }
}
