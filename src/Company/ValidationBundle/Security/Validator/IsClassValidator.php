<?php

namespace Company\ValidationBundle\Security\Validator;

use PropertyManager\Security\Constraint\IsClass;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IsClassValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsClass) {
            throw new UnexpectedTypeException($constraint, IsClass::class);
        }

        /* @var IsClass $constraint */
        if (!is_object($value)) {
            return;
        }

        $classNames = (array)$constraint->className;

        if (self::isInClasses($value, $classNames) xor $constraint->isSameValid) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%currentClass%', get_class($value))
                ->setParameter('%requiredClass%', $constraint->className)
                ->addViolation();
        }
    }

    private static function isInClasses(object $object, array $classNames): bool
    {
        foreach ($classNames as $className) {
            if (is_a($object, $className)) {
                return true;
            }
        }

        return false;
    }
}
