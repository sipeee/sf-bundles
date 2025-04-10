<?php

namespace Company\FormFilterBundle\FilterType;

use Company\FormFilterBundle\Presentation\QueryFilterExpression;

class NotInFilterType implements FilterTypeInterface
{
    public function createExpression(array $filterOptions, string $fieldAlias, string $parameterName, $value): ?QueryFilterExpression
    {
        return new QueryFilterExpression(
            sprintf('%s NOT IN (:%s)', $fieldAlias, $parameterName),
            [$parameterName => $value]
        );
    }
}
