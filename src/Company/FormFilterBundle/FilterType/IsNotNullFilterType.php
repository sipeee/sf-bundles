<?php

namespace Company\FormFilterBundle\FilterType;

use Company\FormFilterBundle\Presentation\QueryFilterExpression;

class IsNotNullFilterType implements FilterTypeInterface
{
    public function createExpression(array $filterOptions, string $fieldAlias, string $parameterName, $value): ?QueryFilterExpression
    {
        $isTrue = in_array($value, [true, 'true', '1', 1], true);

        return new QueryFilterExpression(
            sprintf($isTrue ? '%s IS NOT NULL' : '%s IS NULL', $fieldAlias),
            []
        );
    }
}
