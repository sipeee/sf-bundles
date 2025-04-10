<?php

namespace Company\TypeBundle\Form\Type;

use Company\TypeBundle\Form\Transformer\FormCollectionIdentifierTransformer;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class FormCollectionType extends AbstractType
{
    private const VIEW_BLOCK_TEMPLATE = 'form_collection_item';

    private ManagerRegistry $managerRegistry;
    private PropertyAccessorInterface $accessor;

    public function __construct(ManagerRegistry $managerRegistry, PropertyAccessorInterface $accessor)
    {
        $this->managerRegistry = $managerRegistry;
        $this->accessor = $accessor;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'has_identifier' => false,
            'identifier_fields' => [],
            'row_block_prefix' => self::VIEW_BLOCK_TEMPLATE,
        ]);

        $resolver->addAllowedTypes('has_identifier', ['bool']);
        $resolver->addAllowedTypes('identifier_fields', ['array']);
        $resolver->addAllowedTypes('column_sizes', ['array']);

        $resolver->setNormalizer('identifier_fields', function (Options $options, $identifierFields) {
            return (array) $identifierFields;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['has_identifier']) {
            $prototype = $builder->create($options['prototype_name'], $options['entry_type'], $options['entry_options']);
            $fields = self::getFieldsOfForm($prototype->getForm());

            $builder->addModelTransformer(new FormCollectionIdentifierTransformer(
                $this->managerRegistry,
                $this->accessor,
                $options['identifier_fields'],
                $fields
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['column_sizes'] = $options['column_sizes'];

        $classes = 'form-collection-widget';
        $view->vars['attr']['class'] = isset($view->vars['attr']['class'])
            ? sprintf('%s %s', $classes, $view->vars['attr']['class'])
            : $classes;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'form_collection';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CompanyCollectionType::class;
    }

    private static function getFieldsOfForm(FormInterface $form)
    {
        $fields = [];
        foreach ($form as $fieldName => $childForm) {
            $fields[] = $fieldName;
        }

        return $fields;
    }
}
