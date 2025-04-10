<?php

namespace Company\ExportBundle\Service\FieldTransformer;

class DateToStringTransformer implements FieldTransformerInterface
{
    public function transform($value, array $parameters)
    {
        if (!$value instanceof \DateTimeInterface) {
            return $value;
        }

        $format = $parameters['format'] ?? 'Y-m-d H:i:s';

        return $value->format($format);
    }
}
