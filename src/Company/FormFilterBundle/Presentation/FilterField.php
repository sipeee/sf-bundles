<?php

namespace Company\FormFilterBundle\Presentation;

use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * FilterField.
 */
class FilterField
{
    public string $fieldName;

    public FormFilter $formFilter;

    public string $type;

    public array $options;

    public bool $isEditable;

    public function __construct(string $fieldName, FormFilter $formFilter, string $type = TextType::class, array $options = [], bool $isEditable = true)
    {
        $this->fieldName = $fieldName;
        $this->formFilter = $formFilter;
        $this->type = $type;
        $this->options = $options;
        $this->isEditable = $isEditable;
    }
}
