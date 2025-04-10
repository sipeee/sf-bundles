<?php

namespace Company\ProcessorBundle\Service\DataProcessor;

use Company\Library\QueryIteration\QueryIteratorInterface;

abstract class DateDataProcessorBase extends DataProcessorBase
{
    final public function getLastProcessableValue(): ?int
    {
        return self::convertDateToInteger($this->getLastProcessableDate());
    }

    final public function getProcessableItems(?int $lastProcessedValue, int $lastProcessableValue, int $batchSize): QueryIteratorInterface
    {
        return $this->getProcessableItemsInInterval(
            self::convertIntegerToDate($lastProcessedValue),
            self::convertIntegerToDate($lastProcessableValue),
            $batchSize
        );
    }

    abstract protected function getProcessableItemsInInterval(?\DateTime $lastProcessedDate, \DateTime $lastProcessableDate, int $batchSize): QueryIteratorInterface;

    protected function getLastProcessableDate(): ?\DateTime
    {
        return new \DateTime();
    }

    private static function convertDateToInteger(?\DateTime $date): ?int
    {
        return (null !== $date) ? $date->getTimestamp() : null;
    }

    private static function convertIntegerToDate(?int $value): ?\DateTime
    {
        if (null !== $value) {
            $date = new \DateTime();
            $date->setTimestamp($value);

            return $date;
        }

        return null;
    }
}
