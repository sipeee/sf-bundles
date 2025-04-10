<?php

namespace Company\TypeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType as BaseDateType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'widget' => 'single_text',
            'html5' => false,
            'attr' => ['class' => 'js-datepicker'],
        ]);
    }

    public function getParent(): string
    {
        return BaseDateType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'date_input';
    }
}
