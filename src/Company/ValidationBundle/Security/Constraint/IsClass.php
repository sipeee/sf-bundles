<?php

namespace Company\ValidationBundle\Security\Constraint;

use Company\ValidationBundle\Security\Validator\IsClassValidator;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsClass extends Constraint
{
    public const DEFAULT_OPTION = 'className';

    public $message = 'Class of object is not correct.';
    public $isSameValid = true;
    public $className;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return IsClassValidator::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets(): array
    {
        return [self::PROPERTY_CONSTRAINT];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption(): string
    {
        return self::DEFAULT_OPTION;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions(): array
    {
        return [self::DEFAULT_OPTION];
    }
}
