<?php

namespace Company\DocumentBundle\Service\DocumentTransformer;

use Symfony\Component\HttpFoundation\File\File;

interface DocumentTransformerInterface
{
    public function isSupported(File $file): bool;

    public function transform(File $file, array $parameters): void;
}
