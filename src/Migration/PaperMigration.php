<?php

namespace Michel\PaperORM\Migration;

use DateTime;
use PDOException;
use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\EntityManagerInterface;
use Michel\PaperORM\Generator\SchemaDiffGenerator;
use Michel\PaperORM\Mapper\ColumnMapper;
use Michel\PaperORM\Mapper\EntityMapper;
use Michel\PaperORM\Mapping\Column\DateTimeColumn;
use Michel\PaperORM\Mapping\Column\StringColumn;
use Michel\PaperORM\PaperConnection;
use Michel\PaperORM\Platform\PlatformInterface;
use RuntimeException;
use Throwable;
use function date;
use function file_get_contents;
use function file_put_contents;

final class PaperMigration
{

    /** @var EntityManagerInterface The EntityManager to use for migrations. */
    private EntityManagerInterface $em;
    private PlatformInterface $platform;
    private string $tableName;

    /** @var array<string> List of successfully migrated versions. */
    private array $successList = [];
    /**
     * @var MigrationDirectory
     */
    private MigrationDirectory $directory;

    public static function create(EntityManagerInterface $em, string $tableName, string $directory): self
    {
        return new self($em, $tableName, $directory);
    }

    /**
     * MigrateService constructor.
     * @param EntityManagerInterface $em
     * @param string $tableName
     * @param string $directory
     */
    private function __construct(EntityManagerInterface $em, string $tableName, string $directory)
    {
        $this->em = $em;
        $this->platform = $em->getPlatform();
        $this->tableName = $tableName;
        $this->directory = new MigrationDirectory($directory);
    }

    /**
     * @return array<string>
     */
    public function getVersionAlreadyMigrated(): array
    {
        $version = $this->getConnection()->fetchAll('SELECT version FROM ' . $this->tableName);
        return array_column($version, 'version');
    }

    public function generateMigration(array $sqlUp = [], array $sqlDown = []): string
    {
        $i = 1;
        $file = date('YmdHis') . $i . '.sql';
        $filename = $this->directory->getDir() . DIRECTORY_SEPARATOR . $file;
        while (file_exists($filename)) {
            $i++;
            $filename = rtrim($filename, ($i - 1) . '.sql') . $i . '.sql';
        }

        $migrationContent = <<<'SQL'
-- UP MIGRATION --
%s
-- DOWN MIGRATION --
%s
SQL;
        foreach ($sqlUp as $key => $value) {
            $sqlUp[$key] = rtrim($value, ';') . ';';
        }

        foreach ($sqlDown as $key => $value) {
            $sqlDown[$key] = rtrim($value, ';') . ';';
        }

        if (empty($sqlUp)) {
            $sqlUp[] = '-- Write the SQL code corresponding to the up migration here';
            $sqlUp[] = '-- You can add the necessary SQL statements for updating the database';
        }
        if (empty($sqlDown)) {
            $sqlDown[] = '-- Write the SQL code corresponding to the down migration here';
            $sqlDown[] = '-- You can add the necessary SQL statements for reverting the up migration';
        }

        $migrationContent = sprintf($migrationContent, implode(PHP_EOL, $sqlUp), implode(PHP_EOL, $sqlDown));

        file_put_contents($filename, $migrationContent);
        return $filename;
    }

    public function migrate(): void
    {
        $this->createVersion();

        $this->successList = [];
        $versions = $this->getVersionAlreadyMigrated();
        foreach ($this->directory->getMigrations() as $version => $migration) {

            if (in_array($version, $versions)) {
                continue;
            }

            $this->up($version);
            $this->successList[] = $version;
        }
    }

    public function generateMigrationFromEntities(array $entities): ?string
    {
        $tables = self::transformEntitiesToTables($entities);
        $diff = $this->computeDiffTables($tables);

        if (!$diff) {
            return null;
        }

        return $this->generateMigration($diff['up'], $diff['down']);
    }

    /**
     * Compute the SQL diff (UP part) for preview only.
     * Does not generate any file.
     */
    public function getSqlDiffFromEntities(array $entities): array
    {
        $tables = self::transformEntitiesToTables($entities);
        $diff = $this->computeDiffTables($tables);

        return $diff['up'] ?? [];
    }

    /**
     * Generate and write a migration file based on table differences.
     * Returns the path of the generated file.
     */
    public function generateMigrationFromTables(array $tables): ?string
    {
        $diff = $this->computeDiffTables($tables);
        if (!$diff) {
            return null;
        }

        return $this->generateMigration($diff['up'], $diff['down']);
    }

    public function up(string $version): void
    {
        $migration = $this->directory->getMigration($version);
        $txDdl = $this->platform->supportsTransactionalDDL();
        $conn = $this->getConnection();
        $pdo = $conn->getPdo();
        try {
            if ($txDdl && !$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }
            $executedQueries = [];
            foreach (explode(';' . PHP_EOL, self::contentUp($migration)) as $query) {
                $executed = $this->executeQuery($query);
                if ($executed === false) {
                    continue;
                }
                $executedQueries[] = $query;
            }
            if ($executedQueries === []) {
                throw new RuntimeException("Failed to execute any query for version : " . $version);
            }

            $createdAt = (new DateTime())->format($this->platform->getSchema()->getDateTimeFormatString());
            $rows = $conn->executeStatement('INSERT INTO ' . $this->tableName . ' (version, created_at) VALUES (:version, :created_at)', ['version' => $version, 'created_at' => $createdAt]);
            if ($rows == 0) {
                throw new RuntimeException("Failed to execute insert for version : " . $version);
            }

            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException("Failed to migrate version $version : " . $e->getMessage());
        }

    }

    public function down(string $version): void
    {
        $migration = $this->directory->getMigration($version);
        $txDdl = $this->platform->supportsTransactionalDDL();
        $conn = $this->getConnection();
        $pdo = $conn->getPdo();
        $currentQuery = '';
        try {
            if ($txDdl && !$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }
            foreach (explode(';' . PHP_EOL, self::contentDown($migration)) as $query) {
                $currentQuery = $query;
                $this->executeQuery($query);
            }
            $conn->executeStatement('DELETE FROM ' . $this->tableName . ' WHERE version = :version', ['version' => $version]);

            if ($pdo->inTransaction()) {
                $pdo->commit();
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException(sprintf('Failed to migrate version %s : %s -> %s', $version, $e->getMessage(), $currentQuery));
        }

    }

    public function getSuccessList(): array
    {
        return $this->successList;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    private function createVersion(): void
    {
        $this->platform->createTableIfNotExists($this->tableName, [
            new StringColumn('version', 50),
            new DateTimeColumn('created_at', 'created_at'),
        ]);
    }

    private function getConnection(): PaperConnection
    {
        return $this->em->getConnection();
    }

    private function executeQuery(string $query): bool
    {
        $query = trim($query);
        if (str_starts_with($query = trim($query), '--')) {
            return false;
        }
        $query = rtrim($query, ';') . ';';
        $this->getConnection()->executeStatement($query);
        return true;
    }

    private function computeDiffTables(array $tables): ?array
    {
        $statements = (new SchemaDiffGenerator($this->platform))->generateDiffStatements($tables);

        if (empty($statements['up'])) {
            return null;
        }

        return [
            'up'   => $statements['up'],
            'down' => $statements['down'] ?? '',
        ];
    }

    private static function contentUp(string $migration): string
    {
        return trim(str_replace('-- UP MIGRATION --', '', self::content($migration)[0]));
    }

    private static function contentDown(string $migration): string
    {
        $downContent = self::content($migration)[1];
        return trim($downContent);
    }

    private static function content(string $migration): array
    {
        $migrationContent = file_get_contents($migration);
        $parts = explode('-- DOWN MIGRATION --', $migrationContent, 2);
        return [trim($parts[0]), (isset($parts[1]) ? trim($parts[1]) : '')];
    }

    private static function transformEntitiesToTables(array $entities): array
    {
        $tables = [];
        foreach ($entities as $entity) {
            if (is_subclass_of($entity, EntityInterface::class)) {
                $tableName = EntityMapper::getTable($entity);
                $tables[$tableName] = [
                    'columns' => ColumnMapper::getColumns($entity),
                    'indexes' => [] // TODO IndexMapper::getIndexes($entity)
                ];
            }
        }

        return $tables;
    }
}
