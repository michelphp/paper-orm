<?php

namespace Michel\PaperORM\Command;

use LogicException;
use Michel\Console\Command\CommandInterface;
use Michel\Console\InputInterface;
use Michel\Console\Option\CommandOption;
use Michel\Console\Output\ConsoleOutput;
use Michel\Console\OutputInterface;
use Michel\PaperORM\EntityManager;
use Michel\PaperORM\EntityManagerInterface;

class DatabaseDropCommand implements CommandInterface
{
    private EntityManagerInterface $entityManager;

    private ?string $env;

    public function __construct(EntityManagerInterface $entityManager, ?string $env = null)
    {
        $this->entityManager = $entityManager;
        $this->env = $env;
    }

    public function getName(): string
    {
        return 'paper:database:drop';
    }

    public function getDescription(): string
    {
        return 'Drop the SQL database';
    }

    public function getOptions(): array
    {
        return [
            new CommandOption('force', 'f', 'Force the database drop', true)
        ];
    }

    public function getArguments(): array
    {
        return [];
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = ConsoleOutput::create($output);
        if (!$this->isEnabled()) {
            throw new LogicException('This command is only available in `dev` environment.');
        }

        if (!$input->getOptionValue('force')) {
            throw new LogicException('You must use the --force option to drop the database.');
        }

        $platform = $this->entityManager->getPlatform();
        $platform->dropDatabase();
        $io->success('The SQL database has been successfully dropped.');
    }

    private function isEnabled(): bool
    {
        return 'dev' === $this->env || 'test' === $this->env;
    }
}
