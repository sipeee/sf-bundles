<?php

namespace Company\Library\QueryIteration;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class QueryBuilderBatchIterator implements QueryIteratorInterface
{
    private const SIMPLE_SELECT_MODE = false;
    private const COMPLEX_SELECT_MODE = true;

    private QueryBuilder $queryBuilder;
    /** @var int */
    private bool $isIndexed;

    private int $counterIndex;
    private ?int $count = null;
    /** @var array|array[]|mixed[][] */
    private array $batchEntityDatas;
    private ?int $batchLastId;
    private bool $selectMode;
    private int $batchSize;

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = clone $queryBuilder;
        /** @var Expr\From[] $from */
        $from = $queryBuilder->getDQLPart('from');
        $this->isIndexed = (null !== $from[0]->getIndexBy());

        $this->setComplexSelectMode();
        $this->setBatchSize(self::BATCH_COUNT);
    }

    public function setSimpleSelectMode(): void
    {
        $this->selectMode = self::SIMPLE_SELECT_MODE;
    }

    public function setComplexSelectMode(): void
    {
        $this->selectMode = self::COMPLEX_SELECT_MODE;
    }

    /**
     * @param int $batchSize
     */
    public function setBatchSize($batchSize): void
    {
        $this->batchSize = $batchSize;
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        if (null !== $this->count) {
            return $this->count;
        }

        $countQueryBuilder = clone $this->queryBuilder;
        $countQueryBuilder->select(sprintf('COUNT(DISTINCT %s)', $this->getIdentifierField()));
        $countQueryBuilder->resetDQLPart('orderBy');

        $this->count = $countQueryBuilder
            ->getQuery()
            ->useQueryCache(false)
            ->disableResultCache()
            ->getSingleScalarResult();

        return $this->count;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
        $this->count = null;
        $this->batchLastId = null;
        $this->counterIndex = 0;
        $this->batchEntityDatas = [];

        $this->loadNextEntityBatchData();
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return current($this->batchEntityDatas);
    }

    /**
     * {@inheritDoc}
     */
    public function key(): int
    {
        return ($this->isIndexed)
            ? key($this->batchEntityDatas)
            : $this->counterIndex;
    }

    /**
     * {@inheritDoc}
     */
    public function next(): void
    {
        ++$this->counterIndex;
        next($this->batchEntityDatas);

        if (false === current($this->batchEntityDatas)) {
            $this->loadNextEntityBatchData();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function valid(): bool
    {
        return !empty($this->batchEntityDatas);
    }

    public function loadNextEntityBatchData(): void
    {
        if (self::COMPLEX_SELECT_MODE === $this->selectMode) {
            $ids = $this->queryNextIds();
            $this->batchEntityDatas = $this->queryBatchEntityDataByIds($ids);
            $this->batchLastId = end($ids);
        } else {
            $this->batchEntityDatas = $this->queryNextBatchEntityData();
            $lastRow = end($this->batchEntityDatas);
            $this->batchLastId = (false !== $lastRow)
                ? (is_array($lastRow)
                    ? $lastRow['id']
                    : $lastRow->getId()
                )
                : false;
        }

        reset($this->batchEntityDatas);
    }

    protected function queryNextBatchEntityData(): array
    {
        $queryBuilder = clone $this->queryBuilder;
        $this->addQueryConditions($queryBuilder);

        return $this->executeQueryBuilderWithoutCache($queryBuilder);
    }

    protected function queryNextIds(): array
    {
        $queryBuilder = clone $this->queryBuilder;
        $queryBuilder->select($this->getIdentifierField());
        $this->addQueryConditions($queryBuilder);

        $ids = $this->executeQueryBuilderWithoutCache($queryBuilder);
        $ids = array_map('reset', $ids);

        return $ids;
    }

    protected function queryBatchEntityDataByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $queryBuilder = clone $this->queryBuilder;
        $queryBuilder->andWhere(sprintf('%s IN (:ids)', $this->getIdentifierField()));
        $queryBuilder->setParameter('ids', $ids);

        return $this->executeQueryBuilderWithoutCache($queryBuilder);
    }

    protected function addQueryConditions(QueryBuilder $queryBuilder): void
    {
        if (null !== $this->batchLastId) {
            $queryBuilder->andWhere(sprintf(':batchLastId > %s', $this->getIdentifierField()));
            $queryBuilder->setParameter('batchLastId', $this->batchLastId);
        }

        $recordsLeft = $this->count() - $this->counterIndex;
        $limit = $recordsLeft < $this->batchSize ? $recordsLeft : $this->batchSize;
        $queryBuilder->setMaxResults($limit);
        $queryBuilder->orderBy($this->getIdentifierField(), 'DESC');
    }

    protected function executeQueryBuilderWithoutCache(QueryBuilder $queryBuilder): array
    {
        return $queryBuilder
            ->getQuery()
            ->useQueryCache(false)
            ->useResultCache(false)
            ->getResult();
    }

    protected function getIdentifierField(): string
    {
        return sprintf('%s.id', $this->getRootAlias());
    }

    protected function getRootAlias(): string
    {
        $ra = $this->queryBuilder->getRootAliases();

        return reset($ra);
    }
}
