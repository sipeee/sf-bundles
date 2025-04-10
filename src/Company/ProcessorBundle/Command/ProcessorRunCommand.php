<?php

namespace Company\ProcessorBundle\Command;

use Company\ProcessorBundle\Service\DataProcessorComposite;
use Company\ProcessorBundle\Service\ProcessorManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessorRunCommand extends Command
{
    private const COMMAND = 'company:processor:run';

    /** @var ProcessorManager */
    private $processorManager;
    /** @var DataProcessorComposite */
    private $dataProcessors;

    public function __construct(ProcessorManager $processorManager, DataProcessorComposite $dataProcessors, string $name = null)
    {
        $this->processorManager = $processorManager;
        $this->dataProcessors = $dataProcessors;

        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $command = self::COMMAND;
        $processes = implode("</info>\"\n\"<info>", $this->dataProcessors->getAllProcessNames());
        $this
            ->setName(self::COMMAND)
            ->setDescription(<<<DESCRIPTION
                Run processors for data processing. 
                You can optionally set "batch-size" option to set default batch size of processes, "total" flag to run processes from the start, "from" option to occupy where item will be processed from, "include-process" option to set processes to run in that iteration, "exclude-process" option to set processes not to run in that iteration.

                Available processes:
                "<info>$processes</info>"

                For example: 
                php bin/console $command --<info>batch-size</info>=<comment>100</comment> --<info>from</info>="<comment>2019-01-01 00:00:00</comment>" --<info>include-process</info>="<comment>work-processor:150</comment>" -<info>i</info> "<comment>model-*:50</comment>" -<info>i</info> "<comment>qcomm-*.pro*:75</comment>" -<info>x</info> "<comment>*.samsung*</comment>" -<info>x</info> "<comment>*-mailer</comment>"
                DESCRIPTION
)
            ->addOption(
                'batch-size',
                'b',
                InputOption::VALUE_OPTIONAL,
                'Set default batch size of processes.'
            )
            ->addOption(
                'total',
                't',
                InputOption::VALUE_NONE,
                'Run processes from the start. It reapplies process(es) on processed items.'
            )
            ->addOption(
                'from',
                'f',
                InputOption::VALUE_OPTIONAL,
                'It occupies where item will be processed from. It can be a date or an integer value. If "total" flag is enabled than this option has no any effect.',
            )
            ->addOption(
                'include-process',
                'i',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                <<<DESCRIPTION
                    Set processes to run in that iteration. If not set any than all processes will be ran. You can occupy optionally batch size for those process customly. If set process does not exist than it will be ignored. . You can use * wildcard patterns.

                    For example: php bin/console $command --<info>include-process</info>="<comment>work-processor:150</comment>" -<info>i</info> "<comment>model-processor:50</comment>" -<info>i</info> "<comment>qcomm-*.pro*:75</comment>" -<info>i</info> "<comment>*-mailer</comment>"
                    DESCRIPTION,
                []
            )
            ->addOption(
                'exclude-process',
                'x',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Set processes not to run in that iteration. If set process does not exist than it will be ignored. You can use * wildcard patterns.',
                []
            );
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<comment>Start</comment> processes!');

        $processorManager = $this->processorManager;
        $processorManager->setOutput($output);
        $processorManager->setBatchSize($input->getOption('batch-size'));
        $processorManager->setIncludedProcesses($input->getOption('include-process'));
        $processorManager->setExcludedProcesses($input->getOption('exclude-process'));
        $processorManager->setTotal($input->getOption('total'));
        $processorManager->setFrom(self::normalizeFromOption($input));

        $processorManager->process();

        $output->writeln('<comment>Processes are successfully finished!</comment>');

        return 0;
    }

    private static function normalizeFromOption(InputInterface $input): ?int
    {
        $from = $input->getOption('from');

        if (null === $from) {
            return null;
        }

        return ctype_digit($from)
             ? (int) $from
             : (new \DateTime($from))->getTimestamp();
    }
}
