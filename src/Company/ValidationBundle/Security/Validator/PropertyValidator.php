<?php

namespace Company\ValidationBundle\Security\Validator;

use Company\ValidationBundle\Security\Constraint\Property;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PropertyValidator extends ConstraintValidator
{
    private PropertyAccessorInterface $accessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->accessor = $propertyAccessor;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Property) {
            throw new UnexpectedTypeException($constraint, Property::class);
        }

        if (!is_object($value)) {
            return;
        }

        $context = $this->context;

        foreach ($constraint->fields as $field => $constraints) {
            $fieldValue = $this->accessor->getValue($value, $field);

            $context->getValidator()
                ->inContext($context)
                ->atPath($field)
                ->validate($fieldValue, $constraints);
        }
    }
}
