<?php

namespace Company\FormFilterBundle\Form\Type\Filter;

use Company\FormFilterBundle\Presentation\FilterField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * AbstractFilterForm.
 */
abstract class AbstractFilterType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    final public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'get',
            'csrf_protection' => false,
        ]);

        $this->configureFilterOptions($resolver);
    }

    public function configureFilterOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * @return array|FilterField[]
     */
    abstract public function getFilterFields(array $options = []): array;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->getFilterFields($options) as $filterField) {
            if ($filterField->isEditable) {
                $builder->add($filterField->fieldName, $filterField->type, $filterField->options);
            }
        }
    }
}
