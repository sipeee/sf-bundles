<?php

namespace Company\TypeBundle\Form\Type;

use Company\TypeBundle\Form\Transformer\NumberToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NumberType extends AbstractType
{
    public const TYPE_INTEGER = 'integer';
    public const TYPE_FLOAT = 'float';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'type' => self::TYPE_INTEGER,
            'min' => null,
            'max' => null,
            'sign' => '',
            'signPlacement' => 's',
            'thousandSeparator' => ' ',
            'decimalSeparator' => ',',
            'scale' => null,
            'widgetOptions' => [],
        ]);

        $resolver->setAllowedTypes('type', 'string');
        $resolver->setAllowedValues('type', [self::TYPE_INTEGER, self::TYPE_FLOAT]);
        $resolver->setAllowedTypes('min', ['null', 'int', 'float']);
        $resolver->setAllowedTypes('max', ['null', 'int', 'float']);
        $resolver->setAllowedTypes('sign', ['string']);
        $resolver->setAllowedTypes('scale', ['null', 'integer']);
        $resolver->setAllowedTypes('widgetOptions', ['array']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer(new NumberToStringTransformer($options['type']));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['attr']['class'] = (isset($view->vars['attr']['class']) && !empty($view->vars['attr']['class']))
            ? ($view->vars['attr']['class'].' auto-numeric-widget')
            : 'auto-numeric-widget';

        $widgetOptions = [
            'digitGroupSeparator' => $options['thousandSeparator'],
            'decimalCharacter' => $options['decimalSeparator'],
        ];
        if (null !== $options['scale'] || (self::TYPE_INTEGER === $options['type'])) {
            $widgetOptions['decimalPlaces'] = (self::TYPE_INTEGER !== $options['type'])
                ? $options['scale']
                : 0;
        }
        if ('' !== $options['sign']) {
            $widgetOptions['currencySymbol'] = $options['sign'];
            $widgetOptions['currencySymbolPlacement'] = $options['signPlacement'];
        }
        if (null !== $options['min']) {
            $widgetOptions['minimumValue'] = $options['min'];
        }
        if (null !== $options['max']) {
            $widgetOptions['maximumValue'] = $options['max'];
        }

        $widgetOptions = array_merge($widgetOptions, $options['widgetOptions']);

        $view->vars['attr']['data-auto-numeric-options'] = json_encode($widgetOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'number';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return TextType::class;
    }
}
