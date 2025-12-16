<?php

namespace Michel\PaperORM\Command;

use Michel\Console\Command\CommandInterface;
use Michel\Console\InputInterface;
use Michel\Console\Option\CommandOption;
use Michel\Console\Output\ConsoleOutput;
use Michel\Console\OutputInterface;
use Michel\PaperORM\EntityManager;
use Michel\PaperORM\EntityManagerInterface;

class DatabaseCreateCommand implements CommandInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getName(): string
    {
        return 'paper:database:create';
    }

    public function getDescription(): string
    {
        return 'Creates the database configured for PaperORM';
    }

    public function getOptions(): array
    {
        return [
            new CommandOption('if-not-exists', null, 'Only create the database if it does not already exist', true)
        ];
    }

    public function getArguments(): array
    {
        return [];
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = ConsoleOutput::create($output);
        $platform = $this->entityManager->getPlatform();
        if ($input->hasOption('if-not-exists') && $input->getOptionValue('if-not-exists') === true) {
            $platform->createDatabaseIfNotExists();
            $io->info(sprintf('The SQL database "%s" has been successfully created (if it did not already exist).', $platform->getDatabaseName()));
        } else {
            $platform->createDatabase();
            $io->success(sprintf('The SQL database "%s" has been successfully created.', $platform->getDatabaseName()));
        }
    }
}
