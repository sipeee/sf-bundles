<?php

namespace Company\DocumentBundle\Form\Type;

use Company\DocumentBundle\Form\Transformer\PictureGalleryMultiFileTransformer;
use Company\DocumentBundle\Service\DocumentConfiguration;
use Company\DocumentBundle\Service\MetadataService;
use Company\TypeBundle\Form\Type\CompanyCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PictureGalleryType extends AbstractType
{
    private DocumentConfiguration $documentConfiguration;
    private MetadataService $metadataService;

    public function __construct(DocumentConfiguration $documentConfiguration, MetadataService $metadataService)
    {
        $this->documentConfiguration = $documentConfiguration;
        $this->metadataService = $metadataService;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['entity_class', 'filename_field']);
        $resolver->setDefaults([
            'allow_add' => true,
            'allow_delete' => true,
            'has_title' => false,
            'title_placeholder' => '',
            'mime_types' => PictureFileType::DEFAULT_MIME_TYPES,
        ]);

        $resolver->setAllowedTypes('entity_class', ['string']);
        $resolver->setAllowedTypes('allow_add', ['bool']);
        $resolver->setAllowedTypes('allow_delete', ['bool']);
        $resolver->setAllowedTypes('has_title', ['bool']);
        $resolver->setAllowedTypes('title_placeholder', ['string']);
        $resolver->setAllowedTypes('mime_types', ['array']);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fieldOptions = [
            'required' => false,
            'has_title' => $options['has_title'],
            'label' => false,
            'allow_remove' => $options['allow_delete'],
            'title_placeholder' => $options['title_placeholder'],
            'standalone' => false,
        ];

        if (null !== $options['mime_types']) {
            $fieldOptions['mime_types'] = $options['mime_types'];
        }

        $builder->add('files', CompanyCollectionType::class, [
            'translation_domain' => $options['translation_domain'], // 'validators
            'entry_type' => PictureFileType::class,
            'entry_options' => $fieldOptions,
            'allow_add' => $options['allow_add'],
            'allow_delete' => $options['allow_delete'],
            'add_button_text' => '',
            'label' => false,
            'attr' => [
                'class' => 'clearfix picture-gallery-image-container',
            ],
        ]);

        if ($options['allow_add']) {
            $builder->add('multiFileLastOffset', HiddenType::class);
            $builder->add('multiFiles', CompanyCollectionType::class, [
                'entry_type' => FileType::class,
                'entry_options' => [
                    'required' => false,
                    'multiple' => true,
                    'label' => false,
                    'attr' => [
                        'accept' => implode(', ', $options['mime_types']),
                        'class' => 'multi-picture-upload-widget',
                    ],
                ],
                'allow_add' => $options['allow_add'],
                'allow_delete' => true,
                'add_button_text' => '',
                'label' => false,
            ]);
        }

        $builder->addModelTransformer(new PictureGalleryMultiFileTransformer(
            $this->documentConfiguration,
            $this->metadataService,
            $options['entity_class'],
            $options['filename_field'],
            $options['allow_add']
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['allow_add'] = $options['allow_add'];
        $view->vars['allow_delete'] = $options['allow_add'];
        $view->vars['has_title'] = $options['allow_add'];

        $classes = 'form-control-static picture-gallery-widget';
        $view->vars['attr']['class'] = isset($view->vars['attr']['class'])
            ? sprintf('%s %s', $view->vars['attr']['class'], $classes)
            : $classes;
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return 'picture_gallery';
    }
}
