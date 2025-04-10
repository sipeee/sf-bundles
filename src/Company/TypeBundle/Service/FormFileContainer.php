<?php

namespace Company\TypeBundle\Service;

use Symfony\Component\Form\FormInterface;

class FormFileContainer
{
    /** @var \SplFileInfo[] */
    private array $formFiles = [];

    public function setFormData(FormInterface $form, ?\SplFileInfo $data)
    {
        $this->formFiles[spl_object_hash($form)] = $data;

        return $this;
    }

    public function getFormData(FormInterface $form): ?\SplFileInfo
    {
        return $this->formFiles[spl_object_hash($form)];
    }
}
