<?php

namespace Company\TypeBundle\Form\Transformer;

use Company\TypeBundle\Form\Type\NumberType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class NumberToStringTransformer implements DataTransformerInterface
{
    private string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * Transforms a number type into localized number.
     *
     * @param float|int $value Number value
     *
     * @throws TransformationFailedException if the given value is not numeric
     *                                       or if the value can not be transformed
     *
     * @return string Localized value
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if (!is_numeric($value)) {
            throw new \RuntimeException('Expected a number.');
        }

        return (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        if (!is_numeric($value)) {
            throw new TransformationFailedException(sprintf('The number ("%s") contains unrecognized characters.', $value));
        }

        $normValue = (NumberType::TYPE_INTEGER === $this->type)
            ? (int) $value
            : (float) $value;

        if ($normValue != $value) {
            throw new TransformationFailedException(sprintf('Number ("%s") is in bad format!', $value));
        }

        return $normValue;
    }
}
