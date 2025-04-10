<?php

namespace Company\ValidationBundle\Security\Validator;

use Company\ValidationBundle\Security\Constraint\ConditionalPropertyConstraint;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ConditionalPropertyValidator extends ConstraintValidator
{
    /** @var PropertyAccessorInterface */
    private $accessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->accessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ConditionalPropertyConstraint) {
            throw new UnexpectedTypeException($constraint, ConditionalPropertyConstraint::class);
        }

        if ( $this->isConditionValid($constraint->if, $value) ) {
            $this->applyConstraints($constraint->then, $value);
        } else {
            $this->applyConstraints($constraint->else, $value);
        }
    }

    /**
     * @param mixed $value
     */
    private function isConditionValid(array $constraints, $value): bool
    {
        foreach ($constraints as $constraintItem) {
            $violations = $this->getConstraintViolation($constraintItem, $value);
            if (\count($violations) > 0) {
                return false;
            }
        }

        return true;
    }

    private function applyConstraints(array $constraints, $value): void
    {
        $context = $this->context;
        foreach ($constraints as $constraintItem) {
            $field = $constraintItem['path'];
            $fieldValue = $this->getValueOnPath($value, $field);

            $context->getValidator()
                ->inContext($context)
                ->atPath($field)
                ->validate($fieldValue, $constraintItem['constraints']);
        }
    }

    /**
     * @param mixed $value
     */
    private function getConstraintViolation(array $constraintItem, $value): ConstraintViolationList
    {
        $elementValue = $this->getValueOnPath($value, $constraintItem['path']);
        $constraints = $constraintItem['constraints'];
        if (!\is_array($constraints)) {
            $constraints = [$constraints];
        }

        return $this->getValueViolations($elementValue, $constraints);
    }

    /**
     * @param mixed             $value
     * @param array<Constraint> $constraints
     */
    private function getValueViolations($value, array $constraints): ConstraintViolationList
    {
        $context = $this->context;
        $validator = $context->getValidator();

        $violations = new ConstraintViolationList();
        foreach ($constraints as $constraint) {
            $violations->addAll($validator->validate($value, $constraint));
        }

        return $violations;
    }

    private function getValueOnPath($value, ?string $path)
    {
        return null !== $path
            ? $this->accessor->getValue($value, $path)
            : $value;
    }
}
