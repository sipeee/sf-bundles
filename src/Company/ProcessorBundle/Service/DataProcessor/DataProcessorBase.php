<?php

namespace Company\ProcessorBundle\Service\DataProcessor;

abstract class DataProcessorBase implements DataProcessorInterface
{
    public function isRetryAllowed(): bool
    {
        return true;
    }

    public function calculateAdditionalBatchData(array $processableItems): array
    {
        return [];
    }

    public function processBatchItems(array $items, array $additionalDataRows): void
    {
    }

    public function processItem($item, $additionalData): void
    {
    }

    public function finalizeBatchItems(array $items, array $additionalDataRows): void
    {
    }

    public function handleError(\Throwable $exception): void
    {
    }
}
