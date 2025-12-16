<?php

namespace Michel\PaperORM\Command;

use Michel\Console\Argument\CommandArgument;
use Michel\Console\Command\CommandInterface;
use Michel\Console\InputInterface;
use Michel\Console\Output\ConsoleOutput;
use Michel\Console\OutputInterface;
use Michel\PaperORM\EntityManager;
use Michel\PaperORM\EntityManagerInterface;

class QueryExecuteCommand implements CommandInterface
{
    private EntityManagerInterface $entityManager;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getName(): string
    {
        return 'paper:query:execute';
    }

    public function getDescription(): string
    {
        return 'Execute a SQL query';
    }

    public function getOptions(): array
    {
        return [];
    }

    public function getArguments(): array
    {
        return [
            new CommandArgument('query', true, null, 'The SQL query : select * from users')
        ];
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = ConsoleOutput::create($output);
        $query = $input->hasArgument("query") ? $input->getArgumentValue("query") : null;
        if ($query === null) {
            throw new \LogicException("SQL query is required");
        }
        $io->title('Starting query on ' . $this->entityManager->getPlatform()->getDatabaseName());
        
        $data = $this->entityManager->getConnection()->fetchAll($query);
        $io->listKeyValues([
            'query' => $query,
            'rows' => count($data),
        ]);
        if ($data === []) {
            $io->info('The query yielded an empty result set.');
            return;
        }
        $io->table(array_keys($data[0]), $data);
    }
}
