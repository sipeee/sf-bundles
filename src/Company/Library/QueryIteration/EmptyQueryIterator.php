<?php

namespace Company\Library\QueryIteration;

class EmptyQueryIterator implements QueryIteratorInterface
{
    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function next(): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function valid(): bool
    {
        return false;
    }
}
