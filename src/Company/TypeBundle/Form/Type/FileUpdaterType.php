<?php

namespace Company\TypeBundle\Form\Type;

use Company\TypeBundle\Form\EventSubscriber\FileSubscriber;
use Company\TypeBundle\Service\FormFileContainer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileUpdaterType extends AbstractType
{
    private FormFileContainer $formFileContainer;

    public function __construct(FormFileContainer $formFileContainer)
    {
        $this->formFileContainer = $formFileContainer;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return FileType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'button_text' => function (Options $options) {
                return $options['multiple']
                    ? 'Upload file(s)'
                    : 'Upload file';
            },
            'button_icon' => 'fa-upload',
            'file_placeholder' => function (Options $options) {
                return $options['multiple']
                    ? 'Select file(s)'
                    : 'Select a file ...';
            },
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new FileSubscriber($this->formFileContainer));
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['file'] = $this->formFileContainer->getFormData($form);

        $view->vars['required'] = $options['required'] && empty($view->vars['file']);

        foreach (['button_text', 'button_icon', 'file_placeholder'] as $property) {
            $view->vars[$property] = $options[$property];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return 'file_document';
    }
}
