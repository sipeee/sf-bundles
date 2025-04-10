<?php

namespace Company\FormFilterBundle\FilterType;

use Company\FormFilterBundle\Presentation\QueryFilterExpression;

class InFilterType implements FilterTypeInterface
{
    public function createExpression(array $filterOptions, string $fieldAlias, string $parameterName, $value): ?QueryFilterExpression
    {
        return new QueryFilterExpression(
            sprintf('%s IN (:%s)', $fieldAlias, $parameterName),
            [$parameterName => $value]
        );
    }
}
