<?php

namespace Michel\PaperORM\Command\Migration;

use Michel\Console\Command\CommandInterface;
use Michel\Console\InputInterface;
use Michel\Console\Output\ConsoleOutput;
use Michel\Console\OutputInterface;
use Michel\PaperORM\Migration\PaperMigration;

class MigrationMigrateCommand implements CommandInterface
{
    private PaperMigration $paperMigration;

    public function __construct(PaperMigration $paperMigration)
    {
        $this->paperMigration = $paperMigration;
    }

    public function getName(): string
    {
        return 'paper:migration:migrate';
    }

    public function getDescription(): string
    {
        return 'Execute all migrations';
    }

    public function getOptions(): array
    {
        return [
        ];
    }

    public function getArguments(): array
    {
        return [];
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = ConsoleOutput::create($output);

        $platform = $this->paperMigration->getEntityManager()->getPlatform();

        $io->title('Starting migration migrate on ' . $platform->getDatabaseName());

        $successList = [];
        $error = null;
        try {
            $this->paperMigration->migrate();
            $successList = $this->paperMigration->getSuccessList();
        }catch (\Throwable $exception){
            $error = $exception->getMessage();
        }

        foreach ($successList as $version) {
            $io->success('Migration successfully executed: version ' . $version);
        }

        if (empty($successList) && $error === null) {
            $io->info('No migrations to run. The database is already up to date.');
        }

        if ($error !== null) {
            throw new \RuntimeException('An error occurred during the migration process: ' . $error);
        }
    }
}
