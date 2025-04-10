<?php

namespace Company\ExportBundle\Service;

use Company\ExportBundle\Service\FieldTransformer\FieldTransformerInterface;
use Psr\Container\ContainerInterface;

class FieldTransformerComposite implements FieldTransformerInterface
{
    private ContainerInterface $transformers;

    public function __construct(ContainerInterface $transformers)
    {
        $this->transformers = $transformers;
    }

    public function transform($value, array $parameters)
    {
        $transformers = $this->transformers;
        foreach ($parameters as $transformerConfig) {
            /** @var FieldTransformerInterface $transformer */
            $transformer = $transformers->get($transformerConfig['class']);

            $value = $transformer->transform($value, $parameters);
        }

        return $value;
    }
}
