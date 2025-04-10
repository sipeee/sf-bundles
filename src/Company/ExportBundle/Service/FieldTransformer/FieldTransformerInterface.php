<?php

namespace Company\ExportBundle\Service\FieldTransformer;

interface FieldTransformerInterface
{
    public function transform($value, array $parameters);
}
