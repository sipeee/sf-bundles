<?php

namespace Company\Library\QueryIteration;

class BlockIterator implements QueryIteratorInterface
{
    public const BLOCK_SIZE = self::BATCH_COUNT;

    /** @var int */
    private $blockSize;

    /** @var QueryIteratorInterface */
    private $innerIterator;
    /** @var int */
    private $index;
    /** @var int */
    private $count;
    /** @var array */
    private $currentBlockData;

    public function __construct(QueryIteratorInterface $iterator)
    {
        $this->innerIterator = $iterator;

        $this->setBlockSize(self::BLOCK_SIZE);
    }

    /**
     * @param int $blockSize
     */
    public function setBlockSize($blockSize)
    {
        $this->blockSize = $blockSize;
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        if (null !== $this->count) {
            return $this->count;
        }

        $this->count = (int) ceil($this->innerIterator->count() / self::BLOCK_SIZE);

        return $this->count;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
        $this->index = 0;
        $this->innerIterator->rewind();

        $this->loadNextBlock();
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->currentBlockData;
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * {@inheritDoc}
     */
    public function next(): void
    {
        ++$this->index;
        $iterator = $this->innerIterator;
        if ($iterator->valid()) {
            $iterator->next();
        }

        $this->loadNextBlock();
    }

    /**
     * {@inheritDoc}
     */
    public function valid(): bool
    {
        return !empty($this->currentBlockData);
    }

    public function loadNextBlock(): void
    {
        $iterator = $this->innerIterator;
        $blockLimit = $this->blockSize - 1;
        $this->currentBlockData = [];

        for ($i = 0; $iterator->valid() && $i < $blockLimit; $i++, $iterator->next()) {
            $this->currentBlockData[$iterator->key()] = $iterator->current();
        }

        if ($iterator->valid()) {
            $this->currentBlockData[$iterator->key()] = $iterator->current();
        }
    }
}
