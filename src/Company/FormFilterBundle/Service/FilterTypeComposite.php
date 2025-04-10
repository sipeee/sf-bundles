<?php

namespace Company\FormFilterBundle\Service;

use Company\FormFilterBundle\FilterType\FilterTypeInterface;
use Company\FormFilterBundle\Presentation\QueryFilterExpression;
use Psr\Container\ContainerInterface;

class FilterTypeComposite
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function createExpression(string $filterType, array $filterOptions, string $fieldAlias, string $parameterName, $value): ?QueryFilterExpression
    {
        /** @var FilterTypeInterface $type */
        $type = $this->container->get($filterType);

        return $type->createExpression($filterOptions, $fieldAlias, $parameterName, $value);
    }
}
