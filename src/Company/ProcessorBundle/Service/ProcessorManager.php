<?php

namespace Company\ProcessorBundle\Service;

use Company\ProcessorBundle\Service\DataProcessor\DataProcessorInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessorManager
{
    /** @var ProcessHandler */
    private $processHandler;
    /** @var DataProcessorComposite */
    private $dataProcessors;

    /** @var int */
    private $batchSize;
    /** @var array<int|null> */
    private $includedProcesses;
    /** @var array<string> */
    private $excludedProcesses;
    /** @var bool */
    private $total;
    /** @var int|null */
    private $from;

    /** @var NullOutput */
    private $output;

    public function __construct(ProcessHandler $processHandler, DataProcessorComposite $dataProcessors)
    {
        $this->processHandler = $processHandler;
        $this->dataProcessors = $dataProcessors;

        $this->batchSize = DataProcessorInterface::DEFAULT_BATCH_SIZE;
        $this->includedProcesses = [];
        $this->excludedProcesses = [];
        $this->total = false;
        $this->from = null;

        $this->output = new NullOutput();
    }

    public function setBatchSize(?int $batchSize): self
    {
        if (null !== $batchSize) {
            $this->batchSize = $batchSize;
        }

        return $this;
    }

    public function setIncludedProcesses(array $processes): self
    {
        $this->includedProcesses = [];
        foreach ($processes as $process) {
            $process = explode(':', $process);
            $process[1] = $process[1] ?? null;
            $this->includedProcesses[$process[0]] = $process[1];
        }

        return $this;
    }

    public function setExcludedProcesses(array $processes): self
    {
        $this->excludedProcesses = [];
        foreach ($processes as $process) {
            $this->excludedProcesses[$process] = 1;
        }

        return $this;
    }

    public function setTotal(bool $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function setFrom(?int $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;
        $this->processHandler->setOutput($output);

        return $this;
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Longman\TelegramBot\Exception\TelegramException
     * @throws \Exception
     */
    public function process(): void
    {
        $total = $this->total;
        $from = $this->from;

        foreach ($this->getProcesses() as $processName => $batchSize) {
            $this->output->writeln(sprintf('Processing <comment>%s</comment>...', $processName));
            $this->processHandler->handleProcess($processName, $batchSize, $total, $from);
            $this->output->writeln(sprintf('<comment>%s</comment> is <info>done</info>!', $processName));
        }
    }

    private function getProcesses()
    {
        $processes = [];
        foreach ($this->dataProcessors->getAllProcessNames() as $processName) {
            $batchSize = self::getProcessBatchByMatchingPatterns($processName, $this->includedProcesses, $this->batchSize);
            if (null === $batchSize) {
                continue;
            }

            $isExcluded = self::getProcessBatchByMatchingPatterns($processName, $this->excludedProcesses, null);
            if (null !== $isExcluded) {
                continue;
            }

            $processes[$processName] = $batchSize;
        }

        return $processes;
    }

    /**
     * @param array<int> $patterns
     */
    private function getProcessBatchByMatchingPatterns(string $processName, array $patterns, ?int $defaultBatchSize): ?int
    {
        if (empty($patterns)) {
            return $defaultBatchSize;
        }

        foreach ($patterns as $pattern => $batchSize) {
            if (self::isPatternMatchToProcessName($pattern, $processName)) {
                return $batchSize ?? $defaultBatchSize;
            }
        }

        return null;
    }

    private static function isPatternMatchToProcessName(string $pattern, string $processName): bool
    {
        $pattern = self::convertSimpleToRegexPattern($pattern);

        return (bool) preg_match($pattern, $processName);
    }

    private static function convertSimpleToRegexPattern(string $pattern): string
    {
        $pattern = sprintf('/^%s$/', preg_quote($pattern, '/'));
        $starPattern = preg_quote('*');

        return str_replace($starPattern, '.*', $pattern);
    }
}
