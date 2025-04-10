<?php

namespace Company\UnchangedDisabledFormFieldBundle\Form\Extension;

use Company\UnchangedDisabledFormFieldBundle\Form\EventSubscriber\DisabledFieldSubscriber;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class DisabledFieldExtension extends AbstractTypeExtension
{
    /** @var PropertyAccessorInterface */
    private $accessor;

    public function __construct(PropertyAccessorInterface $accessor)
    {
        $this->accessor = $accessor;
    }

    /**
     * {@inheritDoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('change_disabled', false);

        $resolver->setAllowedTypes('change_disabled', ['bool']);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['compound'] && !$options['change_disabled']) {
            $builder->addEventSubscriber(new DisabledFieldSubscriber($this->accessor));
        }
    }
}
