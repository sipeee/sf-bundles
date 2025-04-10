<?php

namespace Company\ProcessorBundle\Service\DataProcessor;

use Company\Library\QueryIteration\QueryIteratorInterface;

interface DataProcessorInterface
{
    public const DEFAULT_BATCH_SIZE = 100;

    public function getName(): string;

    public function isRetryAllowed(): bool;

    public function getLastProcessableValue(): ?int;

    public function getProcessableItems(?int $lastProcessedValue, int $lastProcessableValue, int $batchSize): QueryIteratorInterface;

    public function calculateAdditionalBatchData(array $processableItems): array;

    public function processBatchItems(array $items, array $additionalDataRows): void;

    public function processItem($item, $additionalData): void;

    public function finalizeBatchItems(array $items, array $additionalDataRows): void;

    public function handleError(\Throwable $exception): void;
}
