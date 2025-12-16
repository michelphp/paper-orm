<?php

namespace Michel\PaperORM\Platform;

use Michel\PaperORM\Mapping\Column\Column;
use Michel\PaperORM\Metadata\ForeignKeyMetadata;
use Michel\PaperORM\Metadata\DatabaseSchemaDiffMetadata;
use Michel\PaperORM\Metadata\ColumnMetadata;
use Michel\PaperORM\Metadata\IndexMetadata;
use Michel\PaperORM\PaperConnection;
use Michel\PaperORM\Schema\SchemaInterface;

/**
 * Interface PlatformInterface
 *
 * This interface defines methods for managing platform-specific database operations.
 */
interface PlatformInterface
{
    /**
     * Retrieves a list of all tables in the current database.
     *
     * @return array Returns an array containing the names of all tables in the database.
     */
    public function listTables(): array;

    /**
     * @param string $tableName
     * @return array<ColumnMetadata>
     */
    public function listTableColumns(string $tableName): array;

    /**
     * @param string $tableName
     * @return array<IndexMetadata>
     */
    public function listTableIndexes(string $tableName): array;

    /**
     * Retrieves a list of all databases available on the platform.
     *
     * @return array Returns an array containing the names of all databases.
     */
    public function listDatabases(): array;

    /**
     * Creates a new database on the platform.
     *
     * @return void
     */
    public function createDatabase(): void;

    /**
     * Creates a new database on the platform if it does not already exist.
     *
     * @return void
     */
    public function createDatabaseIfNotExists(): void;

    /**
     * Retrieves the name of the current database.
     *
     * @return string Returns the name of the current database.
     */
    public function getDatabaseName(): string;

    /**
     * Drops the current database from the platform.
     *
     * @return void
     */
    public function dropDatabase(): void;

    /**
     * @param string $tableName
     * @param array<Column> $columns
     * @return int
     */
    public function createTable(string $tableName, array $columns): int;
    public function createTableIfNotExists(string $tableName, array $columns): int;
    public function dropTable(string $tableName): int;
    public function addColumn(string $tableName, Column $column): int;
    public function dropColumn(string $tableName, Column $column): int;
    public function renameColumn(string $tableName, string $oldColumnName, string $newColumnName): int;
    public function createIndex(IndexMetadata $indexMetadata): int;
    public function dropIndex(IndexMetadata $indexMetadata): int;
    public function createForeignKeyConstraint(string $tableName, ForeignKeyMetadata $foreignKey) :int;
    public function dropForeignKeyConstraints(string $tableName, string $foreignKeyName): int;
    public function getColumnTypeMappings(): array;
    public function convertForeignKeyRuleStringToCode(?string $rule): int;
    public function getMaxLength(): int;
    public function getPrefixIndexName(): string;
    public function getPrefixUniqIndexName(): string;
    public function getPrefixForeignKeyName(): string;
    public function diff(string $tableName, array $columns, array $indexes): DatabaseSchemaDiffMetadata;
    public function getSchema(): SchemaInterface;
    public function supportsTransactionalDDL(): bool;

    public function getConnection(): PaperConnection;
    /**
     * Indicates whether this Platform automatically creates indexes
     * for JoinColumns when generating tables.
     */
    public function autoCreateIndexJoinColumns(): bool;

    /**
     * Indicates whether this Platform automatically creates indexes
     * for PrimaryKey columns when generating tables.
     */
    public function autoCreateIndexPrimaryKeys(): bool;

    /**
     * Indicates whether this Platform automatically creates indexes
     * for columns with a unique constraint (that are neither PK nor JoinColumn).
     */
    public function autoCreateIndexUniqueColumns(): bool;
}

