<?php

namespace Company\FormFilterBundle\FilterType;

use Company\FormFilterBundle\Presentation\QueryFilterExpression;

class BetweenFilterType implements FilterTypeInterface
{
    public function createExpression(array $filterOptions, string $fieldAlias, string $parameterName, $value): ?QueryFilterExpression
    {
        if (!is_array($value)) {
            return null;
        }

        $conditions = [];
        $parameters = [];
        $fromParameterName = $parameterName.'_from';
        $toParameterName = $parameterName.'_to';

        if (isset($value['from'])) {
            $conditions[] = sprintf('%s >= :%s', $fieldAlias, $fromParameterName);
            $parameters[$fromParameterName] = $value['from'];
        }

        if (isset($value['to'])) {
            $conditions[] = sprintf('%s <= :%s', $fieldAlias, $toParameterName);
            $parameters[$toParameterName] = $value['to'];
        }

        if (empty($conditions)) {
            return null;
        }

        return new QueryFilterExpression(
            sprintf('(%s)', implode(') AND (', $conditions)),
            $parameters
        );
    }
}
