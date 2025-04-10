<?php

namespace Company\ExportBundle\Service\FieldTransformer;

class AppendStringTransformer implements FieldTransformerInterface
{
    public function transform($value, array $parameters)
    {
        if (isset($parameters['prefix'])) {
            $value = $parameters['prefix'].$value;
        }

        if (isset($parameters['suffix'])) {
            $value .= $parameters['suffix'];
        }

        return $value;
    }
}
