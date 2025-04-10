<?php

namespace Company\FormFilterBundle\FilterType;

use Company\FormFilterBundle\Presentation\QueryFilterExpression;

class InstanceOfFilterType implements FilterTypeInterface
{
    public function createExpression(array $filterOptions, string $fieldAlias, string $parameterName, $value): ?QueryFilterExpression
    {
        $values = (array) $value;

        $expression = [];
        $parameters = [];
        $index = 0;

        foreach ($values as $value) {
            if (!isset($filterOptions['instanceValues'][$value])) {
                continue;
            }
            ++$index;

            $expression[] = sprintf('%s INSTANCE OF %s', $fieldAlias, $filterOptions['instanceValues'][$value]);
        }

        return (!empty($expression))
            ? new QueryFilterExpression(
                sprintf('((%s))', implode(') OR (', $expression)),
                $parameters
            )
            : null;
    }
}
