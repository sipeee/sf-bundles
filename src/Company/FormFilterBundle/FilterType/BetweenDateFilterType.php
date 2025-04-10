<?php

namespace Company\FormFilterBundle\FilterType;

use Company\FormFilterBundle\Presentation\QueryFilterExpression;
use Company\TypeBundle\Form\DataClass\DateIntervalModel;

class BetweenDateFilterType implements FilterTypeInterface
{
    public function createExpression(array $filterOptions, string $fieldAlias, string $parameterName, $value): ?QueryFilterExpression
    {
        /** @var DateIntervalModel $value */
        $conditions = [];
        $parameters = [];
        $dateFromParameterName = $parameterName.'_from';
        $dateToParameterName = $parameterName.'_to';

        if (null !== $value->getDateFrom()) {
            $conditions[] = sprintf('%s >= :%s', $fieldAlias, $dateFromParameterName);
            $parameters[$dateFromParameterName] = $value->getDateFrom();
        }

        if (null !== $value->getDateTo()) {
            $dateTo = clone $value->getDateTo();
            $dateTo->modify('+1 day');

            $conditions[] = sprintf('%s < :%s', $fieldAlias, $dateToParameterName);
            $parameters[$dateToParameterName] = $dateTo;
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
