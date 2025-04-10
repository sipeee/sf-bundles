<?php

namespace Company\FormFilterBundle\FilterType;

use Company\FormFilterBundle\Presentation\QueryFilterExpression;

class IsNullFilterType implements FilterTypeInterface
{
    public function createExpression(array $filterOptions, string $fieldAlias, string $parameterName, $value): ?QueryFilterExpression
    {
        $notNullFilterType = new IsNotNullFilterType();
        $isTrue = in_array($value, [true, 'true', '1', 1], true);

        return $notNullFilterType->createExpression($filterOptions, $fieldAlias, $parameterName, !$isTrue);
    }
}
