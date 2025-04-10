<?php

namespace Company\Library\QueryIteration;

interface QueryIteratorInterface extends \Iterator, \Countable
{
    public const BATCH_COUNT = 100;
}
