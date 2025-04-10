<?php

namespace Company\ValidationBundle\Security\Constraint;

use Company\ValidationBundle\Security\Validator\ConditionalPropertyValidator;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "CLASS", "METHOD"})
 */
class ConditionalPropertyConstraint extends Constraint
{
    public const IF_PROPERTY = 'if';
    public const THEN_PROPERTY = 'then';

    public $if = [];
    public $then = [];
    public $else = [];

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return ConditionalPropertyValidator::class;
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
    public function getRequiredOptions(): array
    {
        return [self::IF_PROPERTY, self::THEN_PROPERTY];
    }
}
