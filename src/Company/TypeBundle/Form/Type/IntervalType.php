<?php

namespace Company\TypeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntervalType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type' => IntegerType::class,
            'entry_options' => [
                'required' => false,
            ],
            'from_options' => [
                'attr' => [
                    'placeholder' => 'From',
                ],
            ],
            'to_options' => [
                'attr' => [
                    'placeholder' => 'To',
                ],
            ],
        ]);

        $resolver->setAllowedTypes('entry_type', ['string']);
        $resolver->setAllowedTypes('entry_options', ['array']);
        $resolver->setAllowedTypes('from_options', ['array']);
        $resolver->setAllowedTypes('to_options', ['array']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('from', $options['entry_type'], array_merge_recursive($options['entry_options'], $options['from_options']))
            ->add('to', $options['entry_type'], array_merge_recursive($options['entry_options'], $options['to_options']));
    }
}
