<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:content:import-legacy',
    description: 'Imports the versioned KuTaWerk initial data without overwriting existing records.',
)]
final class ImportLegacyContentCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $seedFile = $this->projectDir.'/resources/initial-data.sql';
        $statements = file($seedFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($statements === false) {
            $io->error(sprintf('Initial data file "%s" could not be read.', $seedFile));

            return Command::FAILURE;
        }

        $imported = 0;
        $this->connection->beginTransaction();

        try {
            foreach ($statements as $statement) {
                $imported += $this->connection->executeStatement($statement);
            }

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        $io->success(sprintf(
            'Initial data import completed. %d new records were inserted; existing records were left unchanged.',
            $imported,
        ));

        return Command::SUCCESS;
    }
}
