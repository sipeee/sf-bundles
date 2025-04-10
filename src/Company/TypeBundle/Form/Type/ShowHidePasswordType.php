<?php

namespace Company\TypeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class ShowHidePasswordType extends AbstractType
{
    const WIDGET_CLASS = 'show-hide-password-widget';

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['class'] = isset($view->vars['attr']['class'])
            ? $view->vars['attr']['class'].' '.self::WIDGET_CLASS
            : self::WIDGET_CLASS
        ;
    }

    public function getParent()
    {
        return PasswordType::class;
    }

    public function getBlockPrefix()
    {
        return 'show_hide_password';
    }
}
