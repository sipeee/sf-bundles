<?php

namespace Company\DocumentBundle\Twig\Extension;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DocumentExtension extends AbstractExtension
{
    private PropertyAccessorInterface $accessor;

    public function __construct(PropertyAccessorInterface $accessor)
    {
        $this->accessor = $accessor;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('property', [$this, 'getProperty']),
        ];
    }

    public function getProperty(object $object, string $property)
    {
        return $this->accessor->getValue($object, $property);
    }
}
