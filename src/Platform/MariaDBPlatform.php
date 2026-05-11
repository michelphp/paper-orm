<?php

namespace Michel\PaperORM\Platform;

use LogicException;
use Michel\PaperORM\Mapping\Column\AnyColumn;
use Michel\PaperORM\Mapping\Column\AutoIncrementColumn;
use Michel\PaperORM\Mapping\Column\BinaryColumn;
use Michel\PaperORM\Mapping\Column\BoolColumn;
use Michel\PaperORM\Mapping\Column\Column;
use Michel\PaperORM\Mapping\Column\DateColumn;
use Michel\PaperORM\Mapping\Column\DateTimeColumn;
use Michel\PaperORM\Mapping\Column\DecimalColumn;
use Michel\PaperORM\Mapping\Column\FloatColumn;
use Michel\PaperORM\Mapping\Column\IntColumn;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Mapping\Column\JsonColumn;
use Michel\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Michel\PaperORM\Mapping\Column\SlugColumn;
use Michel\PaperORM\Mapping\Column\StringColumn;
use Michel\PaperORM\Mapping\Column\TextColumn;
use Michel\PaperORM\Mapping\Column\TimestampColumn;
use Michel\PaperORM\Mapping\Column\TokenColumn;
use Michel\PaperORM\Mapping\Column\UuidColumn;
use Michel\PaperORM\Metadata\ColumnMetadata;
use Michel\PaperORM\Metadata\ForeignKeyMetadata;
use Michel\PaperORM\Metadata\IndexMetadata;
use Michel\PaperORM\PaperConnection;
use Michel\PaperORM\Parser\SQLTypeParser;
use Michel\PaperORM\Schema\MariaDBSchema;
use Michel\PaperORM\Schema\SchemaInterface;

class MariaDBPlatform extends AbstractPlatform
{
    private PaperConnection $connection;
    private MariaDBSchema $schema;

    public function __construct(PaperConnection $connection, MariaDBSchema $schema)
    {
        $this->connection = $connection;
        $this->schema = $schema;
    }

    public function getDatabaseName(): string
    {
        return $this->connection->getParams()['path'] ?? '';
    }

    public function listTables(): array
    {
        $rows = $this->connection->fetchAll($this->schema->showTables());
        $tables = [];
        foreach ($rows as $table) {
            $table = array_values($table);
            $tables[] = $table[0];
        }
        rsort($tables, SORT_STRING);
        return $tables;
    }

    /**
     * @param string $tableName
     * @return array<ColumnMetadata>
     */
    public function listTableColumns(string $tableName): array
    {
        $tables = $this->listTables();
        if (!in_array($tableName, $tables)) {
            return [];
        }
        $rows = $this->connection->fetchAll($this->schema->showTableColumns($tableName));
        $foreignKeys = $this->connection->fetchAll($this->schema->showForeignKeys($tableName));
        $columns = [];
        foreach ($rows as $row) {
            $foreignKeyMetadata = null;
            foreach ($foreignKeys as $foreignKey) {
                if ($row['Field'] == $foreignKey['COLUMN_NAME']) {
                    $foreignKeyMetadata = ForeignKeyMetadata::fromArray([
                        'name' => $foreignKey['CONSTRAINT_NAME'],
                        'columns' => [$row['Field']],
                        'referenceTable' => $foreignKey['REFERENCED_TABLE_NAME'],
                        'referenceColumns' => [$foreignKey['REFERENCED_COLUMN_NAME']],
                        'onDelete' => $this->convertForeignKeyRuleStringToCode($foreignKey['DELETE_RULE']),
                        'onUpdate' => $this->convertForeignKeyRuleStringToCode($foreignKey['UPDATE_RULE']),
                    ]);
                    break;
                }
            }
            $columnMetadata = ColumnMetadata::fromArray([
                'name' => $row['Field'],
                'type' => SQLTypeParser::getBaseType($row['Type']),
                'primary' => ($row['Key'] === 'PRI'),
                'foreignKeyMetadata' => $foreignKeyMetadata,
                'null' => ($row['Null'] === 'YES'),
                'default' => $row['Default'] ?? null,
                'comment' => $row['comment'] ?? null,
                'attributes' => SQLTypeParser::extractTypedParameters($row['Type']),
            ]);
            $columns[] = $columnMetadata;
        }
        return $columns;
    }

    /**
     * @param string $tableName
     * @return array<IndexMetadata>
     */
    public function listTableIndexes(string $tableName): array
    {
        $tables = $this->listTables();
        if (!in_array($tableName, $tables)) {
            return [];
        }
        $indexes = $this->connection->fetchAll($this->schema->showTableIndexes($tableName));
        $indexByColumns = [];
        foreach ($indexes as $index) {
            $indexName = $index['Key_name'];
            if (isset($indexByColumns[$indexName])) {
                $indexByColumns[$indexName]['columns'][] = $index['Column_name'];
                continue;
            }
            if ($indexName === 'PRIMARY') {
                continue;
            }
            $indexByColumns[$indexName] = [
                'tableName' => $index['Table'],
                'name' => $indexName,
                'columns' => [$index['Column_name']],
                'unique' => ((int)$index['Non_unique'] === 0),
            ];
        }

        $indexesFormatted = [];
        foreach ($indexByColumns as $idx) {
            $indexesFormatted[] = IndexMetadata::fromArray($idx);
        }
        return $indexesFormatted;
    }

    public function listDatabases(): array
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the platform interface.", __METHOD__));
    }

    public function createDatabase(): void
    {
        $connection = $this->connection->cloneConnectionWithoutDbname();
        $connection->executeStatement($this->schema->createDatabase($this->getDatabaseName()));
    }

    public function createDatabaseIfNotExists(): void
    {
        $connection = $this->connection->cloneConnectionWithoutDbname();
        $connection->executeStatement($this->schema->createDatabaseIfNotExists($this->getDatabaseName()));
    }

    public function dropDatabase(): void
    {
        $connection = $this->connection->cloneConnectionWithoutDbname();
        $database = $this->getDatabaseName();
        $connection->executeStatement($this->schema->dropDatabase($database));
    }

    public function createTable(string $tableName, array $columns): int
    {
        return $this->executeStatement($this->schema->createTable($tableName, $this->mapColumnsToMetadata($tableName, $columns)));
    }

    public function createTableIfNotExists(string $tableName, array $columns, array $options = []): int
    {
        return $this->connection->executeStatement($this->schema->createTableIfNotExists($tableName, $this->mapColumnsToMetadata($tableName, $columns)));
    }

    public function dropTable(string $tableName): int
    {
        return $this->connection->executeStatement($this->schema->dropTable($tableName));
    }

    public function addColumn(string $tableName, Column $column): int
    {
        return $this->connection->executeStatement($this->schema->addColumn($tableName, $this->mapColumnToMetadata($tableName, $column)));
    }

    public function dropColumn(string $tableName, Column $column): int
    {
        return $this->connection->executeStatement($this->schema->dropColumn($tableName, $this->mapColumnToMetadata($tableName, $column)));
    }

    public function renameColumn(string $tableName, string $oldColumnName, string $newColumnName): int
    {
        return $this->connection->executeStatement($this->schema->renameColumn($tableName, $oldColumnName, $newColumnName));
    }

    public function createIndex(IndexMetadata $indexMetadata): int
    {
        return $this->connection->executeStatement($this->schema->createIndex($indexMetadata));
    }

    public function dropIndex(IndexMetadata $indexMetadata): int
    {
        return $this->connection->executeStatement($this->schema->dropIndex($indexMetadata));
    }

    public function createForeignKeyConstraint(string $tableName, ForeignKeyMetadata $foreignKey): int
    {
        return $this->executeStatement($this->schema->createForeignKeyConstraint($tableName, $foreignKey));
    }

    public function dropForeignKeyConstraints(string $tableName, string $foreignKeyName): int
    {
        return $this->executeStatement($this->schema->dropForeignKeyConstraints($tableName, $foreignKeyName));
    }

    public function getMaxLength(): int
    {
        return 20;
    }

    public function getPrefixIndexName(): string
    {
        return 'ix_';
    }

    public function getPrefixUniqIndexName(): string
    {
        return 'uniq_';
    }

    public function getPrefixForeignKeyName(): string
    {
        return 'fk_';
    }

    public function getColumnTypeMappings(): array
    {
        return [
            PrimaryKeyColumn::class => [
                'type' => 'INT',
                'args' => [11]
            ],
            IntColumn::class => [
                'type' => 'INT',
                'args' => [11]
            ],
            JoinColumn::class => [
                'type' => 'INT',
                'args' => [11]
            ],
            DecimalColumn::class => [
                'type' => 'DECIMAL',
                'args' => [10, 5]
            ],
            FloatColumn::class => [
                'type' => 'FLOAT',
                'args' => []
            ],
            DateColumn::class => [
                'type' => 'DATE',
                'args' => []
            ],
            DateTimeColumn::class => [
                'type' => 'DATETIME',
                'args' => []
            ],
            TimestampColumn::class => [
                'type' => 'DATETIME',
                'args' => [],
            ],
            BoolColumn::class => [
                'type' => 'TINYINT',
                'args' => [1]
            ],
            TextColumn::class => [
                'type' => 'TEXT',
                'args' => []
            ],
            JsonColumn::class => [
                'type' => 'LONGTEXT',
                'args' => []
            ],
            StringColumn::class => [
                'type' => 'VARCHAR',
                'args' => [255]
            ],
            SlugColumn::class => [
                'type' => 'VARCHAR',
                'args' => [128]
            ],
            BinaryColumn::class => [
                'type' => 'BLOB',
                'args' => []
            ],
            AnyColumn::class => [
                'type' => 'VARCHAR',
                'args' => [150],
            ],
            UuidColumn::class => [
                'type' => 'VARCHAR',
                'args' => [36],
            ],
            AutoIncrementColumn::class => [
                'type' => 'VARCHAR',
                'args' => [150],
            ],
            TokenColumn::class => [
                'type' => 'VARCHAR',
                'args' => [128],
            ]
        ];
    }

    public function convertForeignKeyRuleStringToCode(?string $rule): int
    {
        $rule = strtoupper($rule);
        switch ($rule) {
            case 'CASCADE':
                return ForeignKeyMetadata::CASCADE;
            case 'SET NULL':
                return ForeignKeyMetadata::SET_NULL;
            case 'RESTRICT':
                return ForeignKeyMetadata::RESTRICT;
            case 'NO ACTION':   /* fall-through */
            default:
                return ForeignKeyMetadata::NO_ACTION;
        }
    }

    public function getSchema(): SchemaInterface
    {
        return $this->schema;
    }

    public function supportsTransactionalDDL(): bool
    {
        return false;
    }

    public function getConnection(): PaperConnection
    {
        return $this->connection;
    }

    public function executeStatement(string $sql): int
    {
        $result = 0;
        foreach (explode(';', $sql) as $stmt) {
            $stmt = trim($stmt);
            if (!empty($stmt)) {
                $result += $this->getConnection()->executeStatement($stmt);
            }
        }
        return $result;
    }

    public function autoCreateIndexJoinColumns(): bool
    {
        return true;
    }

    public function autoCreateIndexPrimaryKeys(): bool
    {
        return false;
    }

    public function autoCreateIndexUniqueColumns(): bool
    {
        return true;
    }
}
