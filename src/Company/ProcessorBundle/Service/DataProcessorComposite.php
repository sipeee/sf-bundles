<?php

namespace Company\ProcessorBundle\Service;

use Company\Library\QueryIteration\QueryIteratorInterface;
use Company\ProcessorBundle\Service\DataProcessor\DataProcessorInterface;

class DataProcessorComposite
{
    /** @var array<DataProcessorInterface> */
    private $dataProcessors;

    public function __construct()
    {
        $this->dataProcessors = [];
    }

    /**
     * @return array<string>
     */
    public function getAllProcessNames(): array
    {
        return array_keys($this->dataProcessors);
    }

    public function addDataProcessor(DataProcessorInterface $dataProcessor): void
    {
        $this->dataProcessors[$dataProcessor->getName()] = $dataProcessor;
    }

    public function isRetryAllowed(string $name): bool
    {
        return $this->getDataProcessorByName($name)->isRetryAllowed();
    }

    public function getLastProcessableValue(string $name): ?int
    {
        return $this->getDataProcessorByName($name)->getLastProcessableValue();
    }

    public function getProcessableItems(string $name, ?int $lastProcessedValue, int $lastProcessableValue, int $batchSize): QueryIteratorInterface
    {
        return $this->getDataProcessorByName($name)->getProcessableItems($lastProcessedValue, $lastProcessableValue, $batchSize);
    }

    public function calculateAdditionalBatchData(string $name, array $processableItems): array
    {
        return $this->getDataProcessorByName($name)->calculateAdditionalBatchData($processableItems);
    }

    public function processBatchItems(string $name, array $items, array $additionalDataRows): void
    {
        $this->getDataProcessorByName($name)->processBatchItems($items, $additionalDataRows);
    }

    public function processItem(string $name, $item, $additionalData): void
    {
        $this->getDataProcessorByName($name)->processItem($item, $additionalData);
    }

    public function finalizeBatchItems(string $name, array $items, array $additionalDataRows): void
    {
        $this->getDataProcessorByName($name)->finalizeBatchItems($items, $additionalDataRows);
    }

    public function handleError(string $name, \Throwable $exception): void
    {
        $this->getDataProcessorByName($name)->handleError($exception);
    }

    private function getDataProcessorByName(string $name): DataProcessorInterface
    {
        return $this->dataProcessors[$name];
    }
}
