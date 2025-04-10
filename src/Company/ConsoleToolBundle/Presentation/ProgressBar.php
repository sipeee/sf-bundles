<?php

namespace Company\ConsoleToolBundle\Presentation;

use Symfony\Component\Console\Output\OutputInterface;

class ProgressBar
{
    public const STATUS_INPROGRESS = 'INPROGRESS';
    public const STATUS_FINISHED = 'FINISHED';
    public const STATUS_FAILED = 'FAILED';

    /** @var OutputInterface */
    private $output;

    /** @var int */
    private $startTime;

    /** @var int|null */
    private $allCount;

    /** @var int|null */
    private $countOfProcessed;

    /** @var string|null */
    private $message;

    /** @var int|null */
    private $lastLineWidth = null;

    /** @var string|null */
    private $status = self::STATUS_INPROGRESS;

    /** @var ProgressBar|null */
    private $parentProgressBar = null;

    public function __construct(
        ?self $parentProgressBar,
        OutputInterface $output,
        $allCount,
        string $message
    ) {
        $this->initializeStartTime();
        $this->synchronizeAllCount($allCount);
        $this->countOfProcessed = 0;
        $this->message = $message;
        $this->status = self::STATUS_INPROGRESS;

        $this->parentProgressBar = $parentProgressBar;

        $this->output = $output;
        $this->output->writeln('');

        $this->printProgressBar();
    }

    public function setProgress(int $processedCount, ?string $message, ?int $allCount): void
    {
        $this->synchronizeAllCount($allCount);
        $this->synchronizeProgressedCount($processedCount);
        $this->synchronizeMessage($message);
    }

    public function finishProgress(): bool
    {
        if ($this->isInProgress()) {
            $this->countOfProcessed = $this->allCount;
            $this->status = self::STATUS_FINISHED;

            return !$this->hasParentProgress();
        } elseif ($this->hasParentProgress()) {
            return $this->parentProgressBar->finishProgress();
        }
        throw new \RuntimeException('You cannot finish a progress that is already finished.');
    }

    public function failProgress(): bool
    {
        if ($this->isInProgress()) {
            $this->status = self::STATUS_FAILED;

            return !$this->hasParentProgress();
        } elseif ($this->hasParentProgress()) {
            return $this->parentProgressBar->failProgress();
        }
        throw new \RuntimeException('You cannot fail a progress that is already failed.');
    }

    public function clearStopedProgresses(): ?self
    {
        if ($this->isInProgress()) {
            return $this;
        }

        $this->output->write(sprintf("\033[1A%s\r", self::generateCoverString($this->lastLineWidth)));

        return $this->parentProgressBar->clearStopedProgresses();
    }

    public function printProgressBar(int $childCountOfProcessed = 0, int $childAllCount = 1): void
    {
        $statusColor = self::getStatusColor($this->status);

        $estimatedCountOfProcessed = $this->countOfProcessed * $childAllCount + $childCountOfProcessed;
        $estimatedAllCount = $this->allCount * $childAllCount;

        $lineToPrint = sprintf(
            '[<fg=%s;options=bold>%s</>] <info>%d</info> <comment>/</comment> <info>%d</info> %s <comment>|</comment> ETA: <info>%s</info>.',
            $statusColor, $this->status,
            $this->countOfProcessed,
            $this->allCount,
            $this->message,
            self::secondsToString(self::calculateEtaInSeconds($this->startTime, $estimatedCountOfProcessed, $estimatedAllCount))
        );

        $lineWidth = strlen(strip_tags($lineToPrint));
        $lineCover = (null !== $this->lastLineWidth && $lineWidth < $this->lastLineWidth)
            ? $this->generateCoverString($this->lastLineWidth - strlen($lineWidth))
            : '';

        $this->output->write("\033[1A");

        if ($this->hasParentProgress()) {
            $this->parentProgressBar->printProgressBar($estimatedCountOfProcessed, $estimatedAllCount);
        }

        $this->output->write(sprintf(
            "%s%s\n",
            $lineToPrint, $lineCover
        ));

        $this->lastLineWidth = $lineWidth;
    }

    /**
     * @return int
     */
    private function synchronizeAllCount(?int $allCount)
    {
        if (null !== $allCount) {
            $this->allCount = $allCount;
        }

        if (null === $this->allCount) {
            throw new \RuntimeException('You need to set all count of progressbar.');
        }
    }

    private function synchronizeProgressedCount(?int $processedCount): void
    {
        if (null !== $processedCount) {
            $this->countOfProcessed = $processedCount;
        }

        if (null === $this->countOfProcessed) {
            throw new \RuntimeException('You need to set processed count of progressbar.');
        }
    }

    private function synchronizeMessage(?string $message): void
    {
        if (null !== $message) {
            $this->message = $message;
        }

        if (null === $this->countOfProcessed) {
            throw new \RuntimeException('You need to set process message of progressbar.');
        }
    }

    private function initializeStartTime(): void
    {
        $this->startTime = time();
    }

    private function isInProgress(): bool
    {
        return self::STATUS_INPROGRESS === $this->status;
    }

    private function hasParentProgress(): bool
    {
        return null !== $this->parentProgressBar;
    }

    private static function calculateEtaInSeconds(int $startTime, ?int $processedCount, ?int $allCount): ?int
    {
        if (empty($allCount)) {
            $etaInSec = 0;
        } elseif (0 === $processedCount) {
            $etaInSec = null;
        } else {
            $elapsedTime = time() - $startTime;
            $remainingItemCount = $allCount - $processedCount;
            $etaInSec = round($elapsedTime * $remainingItemCount / $processedCount);
        }

        return $etaInSec;
    }

    private static function secondsToString(?int $seconds): string
    {
        if (null === $seconds) {
            return 'unknown yet';
        }

        $seconds = $seconds >= 0 ? abs($seconds) : 0;
        $dtF = new \DateTime();
        $dtT = new \DateTime("- $seconds SEC");
        $diff = $dtF->diff($dtT);

        if ($diff->format('%a')) { //If days is not 0
            return $diff->format('%a days, %h hours, %i minutes and %s seconds');
        } elseif (!$diff->format('%a') && $diff->format('%h')) { //If days is 0 and hours is not 0
            return $diff->format('%h hours, %i minutes and %s seconds');
        } elseif (!$diff->format('%a') && !$diff->format('%h') && $diff->format('%i')) { //If days and hours is 0 and minutes is not 0
            return $diff->format('%i minutes, %s seconds');
        }

        return $diff->format('%s seconds');
    }

    private static function generateCoverString(int $lineWidth): string
    {
        return str_repeat(' ', $lineWidth);
    }

    /**
     * @param mixed $status
     *
     * @return string
     */
    private static function getStatusColor($status)
    {
        $tags = self::getStatusTags();

        return $tags[$status];
    }

    /**
     * @return array
     */
    private static function getStatusTags()
    {
        return [
            self::STATUS_INPROGRESS => 'yellow',
            self::STATUS_FINISHED => 'green',
            self::STATUS_FAILED => 'red',
        ];
    }
}
