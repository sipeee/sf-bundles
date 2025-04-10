<?php

namespace Company\FormChangeConfirmationBundle\Form\Extension;

use Company\FormCookieBundle\Form\EventSubscriber\FormCookieSubscriber;
use Company\FormCookieBundle\Service\CachedFormDataService;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangedConfirmationTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'change_confirmation' => false,
//            'change_confirmation' => function (Options $options, $previousValue) {
//                return $previousValue ?? in_array(strtoupper($options['method']), ['POST', 'UPDATE']);
//            },
        ]);

        $resolver->setAllowedTypes('change_confirmation', ['bool']);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!$options['change_confirmation'] || null !== $view->parent) {
            return;
        }

        $values = [];
        self::collectFieldValues($view, $values);

        $view->vars['attr']['data-form-values'] = json_encode($values);
    }

    private static function collectFieldValues(FormView $view, array &$values): void
    {
        if (!empty($view->children)) {
            foreach ($view->children as $child) {
                self::collectFieldValues($child, $values);
            }
        } else {
            if (!$view->vars['compound']) {
                $values[] = ['name' => $view->vars['full_name'], 'value' => $view->vars['value']];
            }
        }
    }
}
