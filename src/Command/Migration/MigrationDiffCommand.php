<?php

namespace Michel\PaperORM\Command\Migration;

use LogicException;
use Michel\Console\Command\CommandInterface;
use Michel\Console\InputInterface;
use Michel\Console\Output\ConsoleOutput;
use Michel\Console\OutputInterface;
use Michel\PaperORM\Collector\EntityDirCollector;
use Michel\PaperORM\Migration\PaperMigration;
use Michel\PaperORM\Tools\EntityExplorer;
use SplFileObject;

class MigrationDiffCommand implements CommandInterface
{
    private PaperMigration $paperMigration;

    private EntityDirCollector $entityDirCollector;

    public function __construct(PaperMigration $paperMigration, EntityDirCollector $entityDirCollector)
    {
        $this->paperMigration = $paperMigration;
        $this->entityDirCollector = $entityDirCollector;
    }

    public function getName(): string
    {
        return 'paper:migration:diff';
    }

    public function getDescription(): string
    {
        return 'Generate a migration diff for the SQL database';
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
        $verbose = $input->getOptionValue('verbose');

        if ($this->entityDirCollector->count() === 0) {
            $suggested = getcwd() . '/src/Entity';

            throw new LogicException(sprintf(
                "No entity directories registered in %s.\n" .
                "You must register at least one directory when building the application.\n\n" .
                "Example:\n" .
                "    \$collector = new EntityDirCollector(['%s']);\n" .
                "    \$command = new %s(\$paperMigration, \$collector);",
                static::class,
                $suggested,
                static::class
            ));
        }

        $platform = $this->paperMigration->getEntityManager()->getPlatform();
        $io->title('Starting migration diff on ' . $platform->getDatabaseName());
        $io->list([
            'Database : ' . $platform->getDatabaseName(),
            'Entities directories : ' . implode(', ', $this->entityDirCollector->all())
        ]);

        $entities = EntityExplorer::getEntities($this->entityDirCollector->all());
        $normalEntities = $entities['normal'];
        $systemEntities = $entities['system'];
        $io->title('Detected entities');
        $io->list([
            'Normal entities : ' . count($normalEntities),
            'System entities : ' . count($systemEntities),
        ]);
        if ($verbose) {
            $io->listKeyValues(array_merge($normalEntities, $systemEntities));
        }

        $executed = false;
        $fileApp = $this->paperMigration->generateMigrationFromEntities($normalEntities);
        if ($fileApp === null) {
            $io->info('No application migration file was generated — schema already in sync.');
        } else {
            $executed = true;
            $io->success('✔ Application migration file generated: ' . $fileApp);
        }

        $fileSystem = $this->paperMigration->generateMigrationFromEntities($systemEntities);
        if ($fileSystem === null) {
            $io->info('No system migration changes detected.');
        } else {
            $executed = true;
            $io->success('✔ System migration file generated: ' . $fileSystem);
        }

        if ($verbose === true) {
            foreach ([$fileSystem, $fileApp] as $file) {
                if ($file === null || !is_file($file)) {
                    continue;
                }

                $io->title('Contents of: ' . basename($file));
                $splFile = new SplFileObject($file);
                $lines = [];
                while (!$splFile->eof()) {
                    $lines[] = $splFile->fgets();
                }
                unset($splFile);
                $io->listKeyValues($lines);
            }
        }
        if ($executed) {
            $io->success('Migration diff process completed successfully.');
        }
    }
}
