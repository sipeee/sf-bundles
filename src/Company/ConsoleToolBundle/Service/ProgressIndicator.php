<?php

namespace Company\ConsoleToolBundle\Service;

use Company\ConsoleToolBundle\Presentation\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ProgressIndicator
{
    /** @var ProgressBar|null */
    private $currentProgressBar = null;

    /** @var OutputInterface */
    private $output;

    /** @var int */
    private $modulus;

    /** @var int */
    private $modulusCounter;

    public function __construct()
    {
        $this->currentProgressBar = null;
        $this->output = new NullOutput();
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function startProgress(int $allCount, int $modulus = 1, string $message = 'item(s) processed.')
    {
        if ($this->hasProgress()) {
            $this->currentProgressBar = $this->currentProgressBar->clearStopedProgresses();
        }

        $this->modulus = $modulus;
        $this->modulusCounter = 0;

        $this->currentProgressBar = new ProgressBar(
            $this->currentProgressBar,
            $this->output,
            $allCount,
            $message
        );
    }

    public function printEta(int $processedCount = null, ?string $message = null, ?int $allCount = null)
    {
        $this->checkHasAnyProgress('printing its state');

        if ($this->hasProgress()) {
            $this->currentProgressBar = $this->currentProgressBar->clearStopedProgresses();
        }

        $this->currentProgressBar->setProgress($processedCount, $message, $allCount);

        ++$this->modulusCounter;
        if (0 === ($this->modulusCounter % $this->modulus)) {
            $this->currentProgressBar->printProgressBar();

            $this->modulusCounter = 0;
        }
    }

    public function finishProgress(): void
    {
        $this->checkHasAnyProgress('finishing it');

        $allFinished = $this->currentProgressBar->finishProgress();

        $this->currentProgressBar->printProgressBar();

        if ($allFinished) {
            $this->clearProgressBars();
        }
    }

    public function failProgress()
    {
        $this->checkHasAnyProgress('failing it');

        $allFailed = $this->currentProgressBar->failProgress();

        $this->currentProgressBar->printProgressBar();

        if ($allFailed) {
            $this->clearProgressBars();
        }
    }

    private function checkHasAnyProgress(string $beforeMessage): void
    {
        if (!$this->hasProgress()) {
            throw new \RuntimeException(sprintf('You need to start progressbar before %s.', $beforeMessage));
        }
    }

    private function hasProgress(): bool
    {
        return null !== $this->currentProgressBar;
    }

    private function clearProgressBars(): void
    {
        $this->currentProgressBar = null;
    }
}
