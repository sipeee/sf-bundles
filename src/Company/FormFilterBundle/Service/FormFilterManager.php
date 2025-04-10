<?php

namespace Company\FormFilterBundle\Service;

use Company\FormFilterBundle\Form\Type\Filter\AbstractFilterType;
use Company\FormFilterBundle\Presentation\FormFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormInterface;

class FormFilterManager
{
    private ManagerRegistry $doctrine;
    private FilterTypeComposite $filterTypes;
    private int $parameterIndex = 0;

    public function __construct(ManagerRegistry $doctrine, FilterTypeComposite $filterTypes)
    {
        $this->doctrine = $doctrine;
        $this->filterTypes = $filterTypes;
    }

    public function filterQueryBuilder(QueryBuilder $queryBuilder, FormInterface $form, array $useHavingForColumns = []): void
    {
        $formFilters = $this->populateFormFilters($queryBuilder, $form);

        foreach ($formFilters as $formFilter) {
            $condition = $this->addConditionByFilter($queryBuilder, $formFilter, null, $useHavingForColumns);

            if (!empty($condition)) {
                $queryBuilder->andWhere($condition);
            }
        }
    }

    public function createQueryBuilderByForm(string $entityClass, FormInterface $form): QueryBuilder
    {
        $queryBuilder = $this->doctrine->getRepository($entityClass)
            ->createQueryBuilder('t')
            ->select('partial t.{id}')
            ->orderBy('t.id', 'DESC');

        $this->filterQueryBuilder($queryBuilder, $form);

        return $queryBuilder;
    }

    /**
     * @return array|FormFilter[]
     */
    private function populateFormFilters(QueryBuilder $queryBuilder, FormInterface $form): array
    {
        $formFilters = [];

        /** @var AbstractFilterType $filterForm */
        $filterForm = $form->getConfig()->getType()->getInnerType();
        $options = $form->getConfig()->getOptions();

        /* @var FormInterface $formElement */
        foreach ($filterForm->getFilterFields($options) as $filterField) {
            $elementName = $filterField->fieldName;
            $formElement = $form[$elementName];
            $value = $formElement->getData();
            //Null values and empty arrays considered as empty
            if (in_array($value, [null, ''], true) || ((is_array($value) || $value instanceof ArrayCollection) && !count($value))) {
                continue;
            }

            $formFilter = $filterField->formFilter;
            $this->setFilterAliasesAndJoinFilterTable($queryBuilder, $formFilter);
            $formFilter->value = $value;
            $formFilters[] = $formFilter;
        }

        return $formFilters;
    }

    private function addConditionByFilter(
        QueryBuilder $queryBuilder,
        FormFilter $formFilter,
        $filterValue = null,
        array $useHavingForColumns = []
    ): string {
        $filterValue = $filterValue ?: $formFilter->getValue();

        if (empty($filterValue) && $filterValue != '0') {
            return '';
        }

        if (!empty($formFilter->subFilters)) {
            return $this->addComplexConditionByFilter($queryBuilder, $formFilter, $filterValue);
        } else {
            return $this->addSingleConditionByFilter($queryBuilder, $formFilter, $filterValue, $useHavingForColumns);
        }
    }

    private function addComplexConditionByFilter(QueryBuilder $queryBuilder, FormFilter $formFilter, $filterValue): string
    {
        //Handle complex filters
        $subWhereClauses = [];
        /** @var FormFilter $subFilter */
        foreach ($formFilter->subFilters as $subFilter) {
            // Pass predefined subfilter values to conditions otherwise
            if (is_null($subFilter->value)) {
                $subFilter->value = $filterValue;
            }

            $subWhereClause = $this->addConditionByFilter($queryBuilder, $subFilter, $subFilter->getValue());

            if (!empty($subWhereClause)) {
                $subWhereClauses[] = $subWhereClause;
            }
        }

        return !empty($subWhereClauses)
            ? '('.implode(sprintf(' %s ', $formFilter->logic), $subWhereClauses).')'
            : '';
    }

    private function addSingleConditionByFilter(QueryBuilder $queryBuilder, FormFilter $formFilter, $filterValue, array $useHavingForColumns): string
    {
        $fieldAlias = $useHavingForColumns[$formFilter->fieldAlias] ?? $formFilter->fieldAlias;

        $filterType = $formFilter->filterType;
        $filterOptions = $formFilter->filterOptions;
        $parameterName = 'param_' . $this->parameterIndex;

        $queryExpression = (null !== $filterType)
            ? $this->filterTypes->createExpression($filterType, $filterOptions, $fieldAlias, $parameterName, $filterValue)
            : null;

        if (null === $queryExpression) {
            return '';
        }

        ++$this->parameterIndex;

        foreach ($queryExpression->getParameters() as $parameterName => $parameterValue) {
            $queryBuilder->setParameter($parameterName, $parameterValue);
        }

        if (isset($useHavingForColumns[$formFilter->fieldAlias])) {
            $queryBuilder->andHaving($queryExpression->getExpression());

            return '';
        } else {
            return $queryExpression->getExpression();
        }

    }

    private function setFilterAliasesAndJoinFilterTable(QueryBuilder $queryBuilder, FormFilter $formFilter): void
    {
        //Handle complex filters
        foreach ($formFilter->subFilters as $subFilter) {
            $this->setFilterAliasesAndJoinFilterTable($queryBuilder, $subFilter);
        }

        if (null === $formFilter->propertyPath) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAlias();
        $propertyPath = $formFilter->propertyPath;
        if (false !== strpos($propertyPath, ':')) {
            $parts = explode(':', $propertyPath);
            $rootAlias = $parts[0];
            $propertyPath = $parts[1];
        }

        $fieldPath = ('' !== $propertyPath)
            ? ($rootAlias.'.'.$propertyPath)
            : $rootAlias;

        //Set field alias
        $formFilter->fieldAlias = $fieldPath;

        $lastDotPos = strrpos($propertyPath, '.');
        //Not joined field
        if (false === $lastDotPos) {
            return;
        }

        $tableSubPath = substr($propertyPath, 0, $lastDotPos);
        $fieldsToJoin = explode('.', $tableSubPath);
        $pathsToJoin = [];
        $tableAlias = $rootAlias;
        foreach ($fieldsToJoin as $i => $field) {
            //Join (continue) to the previous path or to the rootAlias
            $rootPath = $pathsToJoin[$i - 1] ?? $rootAlias;
            $tablePath = $rootPath.'.'.$field;
            $tableAlias = $rootPath.'_'.$field.$formFilter->aliasSuffix;
            $pathsToJoin[$i] = $tableAlias;

            $this->joinTableIfNotJoined($queryBuilder, $tablePath, $tableAlias);
        }

        $fieldName = substr($propertyPath, $lastDotPos + 1);
        $formFilter->fieldAlias = $tableAlias.'.'.$fieldName;
    }

    private function joinTableIfNotJoined(QueryBuilder $queryBuilder, string $tablePath, string $tableAlias)
    {
        $joinDqlParts = $queryBuilder->getDQLParts()['join'];
        /* @var $join Join */
        foreach ($joinDqlParts as $joins) {
            foreach ($joins as $join) {
                if ($join->getAlias() === $tableAlias) {
                    return;
                }
            }
        }

        $queryBuilder->leftJoin($tablePath, $tableAlias);
    }
}
