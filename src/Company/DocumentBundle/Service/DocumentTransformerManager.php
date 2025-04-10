<?php

namespace Company\DocumentBundle\Service;

use Company\DocumentBundle\Service\DocumentTransformer\DocumentTransformerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;

class DocumentTransformerManager
{
    private ContainerInterface $transformers;

    public function __construct(ContainerInterface $transformers)
    {
        $this->transformers = $transformers;
    }

    public function hasTransformation(File $file, array $transformerParameters): bool
    {
        $transformers = $this->transformers;
        foreach ($transformerParameters as $transformerParameter) {
            /** @var DocumentTransformerInterface $transformer */
            $transformer = $transformers->get($transformerParameter['class']);
            if ($transformer->isSupported($file)) {
                return true;
            }
        }

        return false;
    }

    public function transform(File $file, array $transformerParameters): void
    {
        $transformers = $this->transformers;
        foreach ($transformerParameters as $transformerParameter) {
            /** @var DocumentTransformerInterface $transformer */
            $transformer = $transformers->get($transformerParameter['class']);
            if ($transformer->isSupported($file)) {
                $transformer->transform($file, $transformerParameter['parameters']);
            }
        }
    }
}
