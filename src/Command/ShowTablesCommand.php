<?php

namespace Michel\PaperORM\Command;

use Michel\Console\Argument\CommandArgument;
use Michel\Console\Command\CommandInterface;
use Michel\Console\InputInterface;
use Michel\Console\Option\CommandOption;
use Michel\Console\Output\ConsoleOutput;
use Michel\Console\OutputInterface;
use Michel\PaperORM\EntityManagerInterface;
use Michel\PaperORM\Metadata\ColumnMetadata;

class ShowTablesCommand implements CommandInterface
{
    private EntityManagerInterface $entityManager;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getName(): string
    {
        return 'paper:show:tables';
    }

    public function getDescription(): string
    {
        return 'Show the list of tables in the SQL database';
    }

    public function getOptions(): array
    {
        return [
             new CommandOption('columns', null, 'Show the list of columns table ', true)
        ];
    }

    public function getArguments(): array
    {
        return [
            new CommandArgument( 'table', false, null, 'The name of the table')
        ];
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = ConsoleOutput::create($output);
        $tableName = null;
        $withColumns = false;
        if ($input->hasArgument('table')) {
            $tableName = $input->getArgumentValue('table');
        }
        if ($input->hasOption('columns')) {
            $withColumns = $input->getOptionValue('columns');
        }

        $platform = $this->entityManager->getPlatform();
        $io->info('Database : ' . $platform->getDatabaseName());
        $tables = $platform->listTables();
        if ($tableName !== null) {
            if (!in_array($tableName, $tables)) {
                throw new \LogicException(sprintf('The table "%s" does not exist', $tableName));
            }
            $tables = [$tableName];
        }

        if ($withColumns === false) {
            $io->table(['Tables'], array_map(function (string $table) {
                return [$table];
            }, $tables));
        }else {
            foreach ($tables as $table) {
                $io->title(sprintf('Table : %s', $table));
                if ($withColumns === true) {
                    $columns = array_map(function (ColumnMetadata $column) {
                        $data =  $column->toArray();
                        foreach ($data as $key => $value) {
                            $data[$key] = is_array($value) ? json_encode($value) : $value;
                        }
                        return $data;
                    }, $platform->listTableColumns($table));
                    $io->table(array_keys($columns[0]), $columns);
                }
            }
        }
        $io->writeln('');
    }
}
