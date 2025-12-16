<?php

namespace Michel\PaperORM\Command;

use LogicException;
use Michel\Console\Command\CommandInterface;
use Michel\Console\InputInterface;
use Michel\Console\Option\CommandOption;
use Michel\Console\Output\ConsoleOutput;
use Michel\Console\OutputInterface;
use Michel\PaperORM\Collector\EntityDirCollector;
use Michel\PaperORM\Migration\PaperMigration;
use Michel\PaperORM\Tools\EntityExplorer;

class DatabaseSyncCommand implements CommandInterface
{
    private PaperMigration $paperMigration;

    private EntityDirCollector $entityDirCollector;

    private ?string $env;

    /**
     * @param PaperMigration $paperMigration
     * @param EntityDirCollector $entityDirCollector
     * @param string|null $env
     */
    public function __construct(PaperMigration $paperMigration, EntityDirCollector $entityDirCollector, ?string $env = null)
    {
        $this->paperMigration = $paperMigration;
        $this->entityDirCollector = $entityDirCollector;
        $this->env = $env;
    }

    public function getName(): string
    {
        return 'paper:database:sync';
    }

    public function getDescription(): string
    {
        return 'Update the SQL database structure so it matches the current ORM entities.';
    }

    public function getOptions(): array
    {
        return [
            new CommandOption('no-execute', 'n', 'Show the generated SQL statements without executing them.', true)
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
        if (!$this->isEnabled()) {
            throw new LogicException('This command is only available in `dev` environment.');
        }

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

        $noExecute = $input->getOptionValue('no-execute');
        $platform = $this->paperMigration->getEntityManager()->getPlatform();
        $io->title('Starting database sync on ' . $platform->getDatabaseName());
        $io->list([
            'Database : ' . $platform->getDatabaseName(),
            'Entities directories : ' . count($this->entityDirCollector->all())
        ]);
        if ($verbose) {
            $io->listKeyValues($this->entityDirCollector->all());
        }

        $entities = EntityExplorer::getEntities($this->entityDirCollector->all());
        $normalEntities = $entities['normal'];
        $systemEntities = $entities['system'];
        $entities = array_merge($normalEntities, $systemEntities);
        $io->title('Detected entities');
        $io->list([
            'Normal entities : ' . count($normalEntities),
            'System entities : ' . count($systemEntities),
        ]);
        if ($verbose) {
            $io->listKeyValues($entities);
        }

        $updates = $this->paperMigration->getSqlDiffFromEntities($entities);
        if (empty($updates)) {
            $io->info('No differences detected — all entities are already in sync with the database schema.');
            return;
        }

        $count = count($updates);
        $io->writeln("📘 Database synchronization plan");
        $io->writeln("{$count} SQL statements will be executed:");
        $io->writeln("");
        $io->numberedList($updates);
        if ($noExecute) {
            $io->info('Preview mode only — SQL statements were displayed but NOT executed.');
            return;
        }

        $io->writeln("");
        $io->writeln("🚀 Applying changes to database...");
        $conn = $this->paperMigration->getEntityManager()->getConnection();
        foreach ($updates as $sql) {
            $conn->executeStatement($sql);
            $io->writeln("✔ Executed: {$sql}");
        }

        $io->success("Database successfully synchronized.");
    }

    private function isEnabled(): bool
    {
        return 'dev' === $this->env || 'test' === $this->env;
    }
}
