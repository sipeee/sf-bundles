<?php

namespace Company\AutocompleteBundle\Form\Type;

use Company\AutocompleteBundle\Autocomplete\Manager as AutocompleteManager;
use Company\AutocompleteBundle\Form\Transformer\ObjectToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EntityAutocompleteType extends AbstractType
{
    private AutocompleteManager $manager;

    private RouterInterface $router;

    private PropertyAccessorInterface $propertyAccessor;

    private ?TranslatorInterface $translator;

    /**
     * EntityAutocompleteType constructor.
     */
    public function __construct(
        AutocompleteManager $manager,
        RouterInterface $router,
        PropertyAccessorInterface $propertyAccessor,
        ?TranslatorInterface $translator = null
    ) {
        $this->manager = $manager;
        $this->router = $router;
        $this->propertyAccessor = $propertyAccessor;
        $this->translator = $translator;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'descriptor',
        ]);

        $resolver->setDefaults([
            'compound' => false,
            'placeholder' => null,
            'multiple' => false,
            'is_creation_allowed' => false,
            'params' => null,
            'minimum_input_length' => 0,
            'width' => '100%',
            'items_per_page' => 20,
            'quiet_millis' => 200,
            'cache' => false,
            'container_css_class' => null,
            'dropdown_css_class' => null,
            'allow_clear' => function (Options $options, ?string $previousValue): bool {
                return (null !== $options['required'])
                    ? !$options['required']
                    : (bool)($previousValue);
            },
        ]);

        $resolver->setAllowedTypes('descriptor', ['string']);
        $resolver->setAllowedTypes('multiple', ['bool']);
        $resolver->setAllowedTypes('placeholder', ['null', 'string']);
        $resolver->setAllowedTypes('is_creation_allowed', ['bool']);
        $resolver->setAllowedTypes('params', ['null', 'array']);
        $resolver->setAllowedTypes('minimum_input_length', ['int']);
        $resolver->setAllowedTypes('width', ['string']);
        $resolver->setAllowedTypes('items_per_page', ['int']);
        $resolver->setAllowedTypes('quiet_millis', ['int']);
        $resolver->setAllowedTypes('cache', ['bool']);
        $resolver->setAllowedTypes('container_css_class', ['string', 'null']);
        $resolver->setAllowedTypes('dropdown_css_class', ['string', 'null']);
        $resolver->setAllowedTypes('allow_clear', ['bool']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'entity_autocomplete';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new ObjectToIdTransformer(
            $this->manager,
            $options['descriptor'],
            $options['multiple'],
            $options['is_creation_allowed'],
            $this->propertyAccessor
        );
        $builder->addModelTransformer($transformer);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (null !== $this->translator && isset($options['placeholder']) && $options['translation_domain'] !== false) {
            $options['placeholder'] = $this->translator->trans($options['placeholder'], [], $options['translation_domain']);
        }

        $parameters = array_intersect_key($options, [
            'placeholder' => null,
            'multiple' => null,
            'minimum_input_length' => null,
            'width' => null,
            'items_per_page' => null,
            'quiet_millis' => null,
            'cache' => null,
            'is_creation_allowed' => null,
            'params' => [],
            'container_css_class' => null,
            'allow_clear' => null,
        ]);

        $parameters['url'] = $this->router->generate('company_autocomplete_callback', [
            'descriptor' => $options['descriptor'],
        ]);

        $view->vars['attr']['data-autocomplete-options'] = json_encode($parameters);
        $view->vars['multiple'] = $options['multiple'];

        if (isset($view->vars['attr']['class'])) {
            $view->vars['attr']['class'] .= ' select2-entity-autocomplete-widget';
        } else {
            $view->vars['attr']['class'] = 'select2-entity-autocomplete-widget';
        }
    }
}
