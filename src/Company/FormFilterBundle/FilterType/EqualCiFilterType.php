<?php

namespace Company\FormFilterBundle\FilterType;

use Company\FormFilterBundle\Presentation\QueryFilterExpression;

class EqualCiFilterType implements FilterTypeInterface
{
    public function createExpression(array $filterOptions, string $fieldAlias, string $parameterName, $value): ?QueryFilterExpression
    {
        return new QueryFilterExpression(
            sprintf('LOWER(%s) = LOWER(:%s)', $fieldAlias, $parameterName),
            [$parameterName => $value]
        );
    }
}
