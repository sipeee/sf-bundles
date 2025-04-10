<?php

namespace Company\ValidationBundle\Security\Constraint;

use Company\ValidationBundle\Security\Validator\PropertyValidator;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "CLASS", "METHOD"})
 */
class Property extends Constraint
{
    private const FIELDS_PROPERTY = 'fields';

    public array $fields;

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return PropertyValidator::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets(): array
    {
        return [self::PROPERTY_CONSTRAINT, self::CLASS_CONSTRAINT];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption(): string
    {
        return self::FIELDS_PROPERTY;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions(): array
    {
        return [self::FIELDS_PROPERTY];
    }
}
