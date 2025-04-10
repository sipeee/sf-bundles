<?php

namespace Company\AutocompleteBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class TextAutocompleteType extends AbstractType
{
    public const AUTOCOMPLETE_COUNT_LIMIT = 20;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * TextAutocompleteType constructor.
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'descriptor',
        ]);

        $resolver->setDefaults([
            'compound' => false,
            'minimum_input_length' => 0,
            'quiet_millis' => 200,
            'params' => [],
            'items_per_page' => self::AUTOCOMPLETE_COUNT_LIMIT,
        ]);
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
        return 'text_autocomplete';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $parameters = array_intersect_key($options, [
            'minimum_input_length' => null,
            'quiet_millis' => null,
            'items_per_page' => self::AUTOCOMPLETE_COUNT_LIMIT,
            'params' => [],
        ]);

        $parameters['url'] = $this->router->generate('company_autocomplete_callback', [
            'descriptor' => $options['descriptor'],
        ]);

        $view->vars['attr']['data-autocomplete-options'] = json_encode($parameters);

        if (isset($view->vars['attr']['class'])) {
            $view->vars['attr']['class'] .= ' ui-text-autocomplete-widget';
        } else {
            $view->vars['attr']['class'] = 'ui-text-autocomplete-widget';
        }
    }
}
