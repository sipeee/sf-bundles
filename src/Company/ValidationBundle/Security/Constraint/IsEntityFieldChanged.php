<?php

namespace Company\ValidationBundle\Security\Constraint;

use Company\ValidationBundle\Security\Validator\IsEntityFieldChangedValidator;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsEntityFieldChanged extends Constraint
{
    public const FIELD_PROPERTY = 'field';

    public $message = 'Field changed!';
    public $isChangedValid = false;
    public $field = [];

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return IsEntityFieldChangedValidator::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption(): string
    {
        return self::FIELD_PROPERTY;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions(): array
    {
        return [self::FIELD_PROPERTY];
    }
}
