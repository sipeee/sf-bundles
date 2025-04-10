<?php

namespace Company\ValidationBundle\Security\Validator;

use PropertyManager\Security\Constraint\IsEntityFieldChanged;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IsEntityFieldChangedValidator extends ConstraintValidator
{
    /** @var ManagerRegistry */
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsEntityFieldChanged) {
            throw new UnexpectedTypeException($constraint, IsEntityFieldChanged::class);
        }

        if (!is_object($value)) {
            return;
        }

        $class = get_class($value);
        /** @var EntityManager $em */
        $em = $this->doctrine->getManagerForClass($class);
        if (null === $em) {
            return;
        }

        $field = $constraint->field;
        $originalData = $em->getUnitOfWork()->getOriginalEntityData($value);
        $originalValue = $originalData[$field] ?? null;

        $metadata = $em->getClassMetadata($class);
        $currentValue = $metadata->getFieldValue($value, $field);

        if ($currentValue !== $originalValue xor $constraint->isChangedValid) {
            $this->context->buildViolation($constraint->message)
                ->atPath($field)
                ->addViolation();
        }
    }
}
