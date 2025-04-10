<?php

namespace Company\DocumentBundle\Form\Type;

use Company\DocumentBundle\Presentation\DocumentFormData;
use Company\DocumentBundle\Presentation\DocumentVariant;
use Company\TypeBundle\Form\Type\FileUpdaterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PictureFileType extends AbstractType
{
    public const DEFAULT_MIME_TYPES = [
        'image/gif',
        'image/jpeg',
        'image/png',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/rtf',
        'text/plain',
    ];

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DocumentFormData::class,
            'by_reference' => false,
            'required' => false,
            'has_title' => false,
            'title_placeholder' => '',
            'allow_remove' => function (Options $options, $previousValue) {
                if (null !== $previousValue) {
                    return $previousValue;
                }

                return !$options['required'];
            },
            'mime_types' => self::DEFAULT_MIME_TYPES,
            'standalone' => true,
        ]);

        $resolver->setAllowedTypes('has_title', ['bool']);
        $resolver->setAllowedTypes('title_placeholder', ['string']);
        $resolver->setAllowedTypes('allow_remove', ['bool']);
        $resolver->setAllowedTypes('mime_types', ['array']);
        $resolver->setAllowedTypes('standalone', ['bool']);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('file', FileUpdaterType::class, [
            'required' => $options['required'],
            'attr' => [
                'accept' => implode(', ', $options['mime_types']),
            ],
        ]);

        if ($options['has_title']) {
            $titleOptions = [
                'label' => false,
                'required' => false,
            ];
            if (!empty($options['title_placeholder'])) {
                $titleOptions['attr']['placeholder'] = $options['title_placeholder'];
            }
            $builder->add('title', TextType::class, $titleOptions);
        }

        if ($options['allow_remove']) {
            $builder->add('removable', CheckboxType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'file-remove-widget',
                ],
            ]);
        }
        $builder->add('identifier', HiddenType::class, [
            'required' => false,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var DocumentFormData $formData */
        $formData = $form->getData();

        if (null !== $formData) {
            $url = $formData->getUrl();
            $originalUrl = $formData->getOriginalUrl();
            $iconLink = $formData->getIconLink();
            $isDisplayedOnWeb = $formData->isDisplayedOnWeb();
            $isImage = $formData->isImage();
            $title = $formData->getTitle();
        } else {
            $url = null;
            $originalUrl = null;
            $iconLink = null;
            $isDisplayedOnWeb = false;
            $isImage = false;
            $title = null;
        }

        $classes = 'picture-file-upload-widget';
        if (null !== $url) {
            $classes .= ' selected';
            if (!$isImage) {
                $classes .= 'icon';
            }
        }

        $view->vars['has_title'] = $options['has_title'];
        $view->vars['allow_remove'] = $options['allow_remove'];
        $view->vars['thumbnail_data_url'] = $url;
        $view->vars['original_data_url'] = $originalUrl;
        $view->vars['icon_link'] = $iconLink;
        $view->vars['title'] = $title;
        $view->vars['is_image'] = $isImage;
        $view->vars['is_displayed_on_web'] = $isDisplayedOnWeb;
        $view->vars['attr']['data-accepted-mime-types'] = json_encode($options['mime_types']);
        $view->vars['attr']['data-file-icons'] = json_encode(DocumentVariant::FILE_ICONS);
        $view->vars['attr']['data-standalone'] = json_encode($options['standalone']);

        $view->vars['attr']['class'] = isset($view->vars['att']['class'])
            ? sprintf('%s %s', $view->vars['att']['class'], $classes)
            : $classes;
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $prefixes = $view->children['file']->vars['block_prefixes'];
        /** @var FormInterface $file */
        $file = $form['file'];
        $position = array_search($file->getConfig()->getType()->getBlockPrefix(), $prefixes, true);

        $view->vars['name'] = null;
        $view->vars['full_name'] = null;

        if (false !== $position) {
            unset($prefixes[$position]);
            $view->children['file']->vars['block_prefixes'] = array_values($prefixes);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return 'picture_file';
    }
}
