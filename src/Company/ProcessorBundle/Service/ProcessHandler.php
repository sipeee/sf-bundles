<?php

namespace Company\ProcessorBundle\Service;

use Company\ConsoleToolBundle\Service\ProgressIndicator;
use Company\Library\QueryIteration\BlockIterator;
use Company\Library\QueryIteration\QueryIteratorInterface;
use Company\ProcessorBundle\Service\DataProcessor\DataProcessorInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessHandler
{
    public const CACHE_KEY_PREFIX = 'process.last_run_value.';
    /** @var DataProcessorComposite */
    private $dataProcessors;
    /** @var CacheItemPoolInterface */
    private $cachePool;
    /** @var ProgressIndicator */
    private $progressIndicator;

    public function __construct(DataProcessorComposite $dataProcessors, CacheItemPoolInterface $cachePool, ProgressIndicator $progressIndicator)
    {
        $this->dataProcessors = $dataProcessors;
        $this->cachePool = $cachePool;
        $this->progressIndicator = $progressIndicator;
    }

    public function setOutput(OutputInterface $output): self
    {
        $this->progressIndicator->setOutput($output);

        return $this;
    }

    public function handleProcess(string $processName, ?int $batchSize, bool $total, ?int $from)
    {
        $lastProcessableValue = $this->dataProcessors->getLastProcessableValue($processName);
        if (null === $lastProcessableValue) {
            return;
        }

        if (null === $batchSize) {
            $batchSize = DataProcessorInterface::DEFAULT_BATCH_SIZE;
        }

        $lastProcessedValue = $this->getLastProcessedValue($processName, $total, $from);
        $this->setLastProcessedValueIfRetryAllowed($processName, $lastProcessableValue, false);

        $processableItems = $this->dataProcessors->getProcessableItems($processName, $lastProcessedValue, $lastProcessableValue, $batchSize);

        try {
            $this->handleIteration($processableItems, $processName);
        } catch (\Throwable $e) {
            $this->dataProcessors->handleError($processName, $e);

            throw $e;
        }

        $this->setLastProcessedValueIfRetryAllowed($processName, $lastProcessableValue, true);
    }

    private function handleIteration(QueryIteratorInterface $processableItems, string $processName): void
    {
        $itemCount = $processableItems->count();
        $processableItemBlocks = new BlockIterator($processableItems);

        $processedCount = 0;
        $this->progressIndicator->startProgress($itemCount);

        try {
            foreach ($processableItemBlocks as $processableItems) {
                $this->handleProcessableItems($processName, $processableItems);

                $processedCount += count($processableItems);
                $this->progressIndicator->printEta($processedCount);
            }

            $this->progressIndicator->finishProgress();
        } catch (\Throwable $e) {
            $this->progressIndicator->failProgress();

            throw $e;
        }
    }

    private function handleProcessableItems(string $processName, array $processableItems): void
    {
        $additionalDataRows = $this->dataProcessors->calculateAdditionalBatchData($processName, $processableItems);

        $this->dataProcessors->processBatchItems($processName, $processableItems, $additionalDataRows);

        foreach ($processableItems as $index => $item) {
            $this->dataProcessors->processItem($processName, $item, $additionalDataRows[$index] ?? null);
        }

        $this->dataProcessors->finalizeBatchItems($processName, $processableItems, $additionalDataRows);
    }

    private function getLastProcessedValue(string $name, bool $total, ?int $from): ?int
    {
        if ($total) {
            return null;
        }

        if (null !== $from) {
            return $from;
        }

        $key = self::getCacheKey($name);
        $cacheItem = $this->cachePool->getItem($key);

        return null !== $cacheItem->get()
            ? $cacheItem->get()
            : null;
    }

    private function setLastProcessedValueIfRetryAllowed(string $name, int $value, bool $retry): void
    {
        if ($retry === $this->dataProcessors->isRetryAllowed($name)) {
            $this->setLastProcessedValue($name, $value);
        }
    }

    private function setLastProcessedValue(string $name, int $value): void
    {
        $cachePool = $this->cachePool;

        $item = $cachePool->getItem(self::getCacheKey($name));
        $item->set($value);

        $cachePool->save($item);
    }

    private static function getCacheKey(string $name): string
    {
        return sprintf('%s%s', self::CACHE_KEY_PREFIX, $name);
    }
}
