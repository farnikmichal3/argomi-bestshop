<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\Shared\Infrastructure\Outbox\DatabaseOutboxWorker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'outbox:work',
    description: 'Zpracuje jednu dávku dostupných zpráv Transactional Outbox',
)]
final class OutboxWorkerCommand extends Command
{
    public function __construct(
        private readonly DatabaseOutboxWorker $worker,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'limit',
            null,
            InputOption::VALUE_REQUIRED,
            'Maximální počet zpráv v jedné dávce (1 až 100)',
            '10',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $batchSize = filter_var($input->getOption('limit'), FILTER_VALIDATE_INT);

        if (!is_int($batchSize) || $batchSize < 1 || $batchSize > 100) {
            $io->error('Limit dávky musí být celé číslo v rozsahu 1 až 100.');

            return Command::FAILURE;
        }

        $processed = $this->worker->runOnce($batchSize);
        $io->success(sprintf('Převzaté a zpracované zprávy v dávce: %d.', $processed));

        return Command::SUCCESS;
    }
}
