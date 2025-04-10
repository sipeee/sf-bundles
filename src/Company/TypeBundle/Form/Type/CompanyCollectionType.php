<?php

namespace Company\TypeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType as CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyCollectionType extends AbstractType
{
    private const VIEW_BLOCK_TEMPLATE = 'company_collection_item';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'add_button_text' => 'Add new item',
            'row_block_prefix' => self::VIEW_BLOCK_TEMPLATE,
        ]);

        $resolver->addAllowedTypes('add_button_text', ['string']);
        $resolver->addAllowedTypes('row_block_prefix', ['string']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $elements = $form->getViewData();

        if (\is_array($elements) && !empty($elements)) {
            $keys = array_keys($elements);
            $lastIndex = end($keys);

            $view->vars['attr']['data-last-index'] = $lastIndex;
        } else {
            $view->vars['attr']['data-last-index'] = -1;
        }

        $class = 'company-collection-widget';
        $view->vars['attr']['class'] = isset($view->vars['attr']['class'])
            ? sprintf('%s %s', $view->vars['attr']['class'], $class)
            : $class;

        $view->vars['add_button_text'] = $options['add_button_text'];
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['allow_add']) {
            $prototype = $view->vars['prototype'];
            $this->addViewBlockTemplate($prototype, $options['row_block_prefix']);
            $prototype->vars['allow_add'] = $options['allow_add'];
            $prototype->vars['allow_delete'] = $options['allow_delete'];
        }
        foreach ($view->children as $child) {
            $this->addViewBlockTemplate($child, $options['row_block_prefix']);
            $child->vars['allow_add'] = $options['allow_add'];
            $child->vars['allow_delete'] = $options['allow_delete'];
        }

        $this->removeViewBlockTemplate($view, 'collection');
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'company_collection';
    }

    public function removeViewBlockTemplate(FormView $view, string $rowBlockPrefix): void
    {
        $prefixes = $view->vars['block_prefixes'];
        $position = array_search($rowBlockPrefix, $prefixes, true);

        if (false !== $position) {
            unset($prefixes[$position]);
            $view->vars['block_prefixes'] = array_values($prefixes);
        }
    }

    private function addViewBlockTemplate(FormView $view, string $rowBlockPrefix): void
    {
        $view->vars['block_prefixes'][] = $rowBlockPrefix;
    }
}
