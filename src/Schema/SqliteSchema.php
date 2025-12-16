<?php

namespace Michel\PaperORM\Schema;

use LogicException;
use Michel\PaperORM\Metadata\ForeignKeyMetadata;
use Michel\PaperORM\Metadata\ColumnMetadata;
use Michel\PaperORM\Metadata\IndexMetadata;
use Michel\PaperORM\Schema\Traits\IdentifierQuotingTrait;
use SQLite3;

class SqliteSchema implements SchemaInterface
{

    use IdentifierQuotingTrait;

    public function showDatabases(): string
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the schema interface.", __METHOD__));
    }

    public function showTables(): string
    {
        return "SELECT name FROM  sqlite_schema WHERE  type ='table' AND name NOT LIKE 'sqlite_%'";
    }

    public function showTableColumns(string $tableName): string
    {
        return sprintf("PRAGMA table_info('%s')", $tableName);
    }

    public function showForeignKeys(string $tableName): string
    {
        return sprintf("PRAGMA foreign_key_list('%s')", $tableName);
    }

    public function showTableIndexes(string $tableName): string
    {
        return sprintf("PRAGMA index_list('%s')", $tableName);
    }

    public function createDatabase(string $databaseName): string
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the schema interface.", __METHOD__));
    }

    public function createDatabaseIfNotExists(string $databaseName): string
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the schema interface.", __METHOD__));
    }

    public function dropDatabase(string $databaseName): string
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the schema interface.", __METHOD__));
    }

    /**
     * @param string $tableName
     * @param array<ColumnMetadata> $columns
     * @param array $options
     * @return string
     */
    public function createTable(string $tableName, array $columns, array $options = []): string
    {
        $lines = [];
        $foreignKeys = [];
        foreach ($columns as $columnMetadata) {
            $line = sprintf('%s %s', $this->quote($columnMetadata->getName()), $columnMetadata->getTypeWithAttributes());
            if ($columnMetadata->isPrimary()) {
                $line .= ' PRIMARY KEY AUTOINCREMENT';
            }
            if (!$columnMetadata->isNullable()) {
                $line .= ' NOT NULL';
            }
            if ($columnMetadata->getDefaultValue() !== null) {
                $line .= sprintf(' DEFAULT %s', $columnMetadata->getDefaultValuePrintable());
            }
            $lines[] = $line;

            if (!empty($columnMetadata->getForeignKeyMetadata())) {
                $foreignKeys[] = $columnMetadata->getForeignKeyMetadata();
            }
        }

        foreach ($foreignKeys as $foreignKey) {
            $lines[] = $this->foreignKeyConstraints($foreignKey);
        }
        $options['indexes'] = $options['indexes'] ?? [];

        $linesString = implode(',', $lines);

        $createTable = sprintf("CREATE TABLE %s (%s)", $this->quote($tableName), $linesString);

        $indexesSql = [];
        foreach ($options['indexes'] as $index) {
            $indexesSql[] = $this->createIndex($index);
        }

        return $createTable . ';' . implode(';', $indexesSql);
    }

    public function createTableIfNotExists(string $tableName, array $columns, array $options = []): string
    {
        $createTable = $this->createTable($tableName, $columns, $options);
        return str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $createTable);
    }

    public function createForeignKeyConstraint(string $tableName, ForeignKeyMetadata $foreignKey): string
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the schema interface.", __METHOD__));
    }

    public function dropForeignKeyConstraints(string $tableName, string $foreignKeyName): string
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the schema interface.", __METHOD__));
    }

    public function dropTable(string $tableName): string
    {
        return sprintf('DROP TABLE %s', $this->quote($tableName));
    }

    public function renameTable(string $oldTableName, string $newTableName): string
    {
        return sprintf('ALTER TABLE %s RENAME TO %s', $this->quote($oldTableName), $this->quote($newTableName));
    }

    public function addColumn(string $tableName, ColumnMetadata $columnMetadata): string
    {
        $sql = sprintf('ALTER TABLE %s ADD %s %s', $this->quote($tableName), $this->quote($columnMetadata->getName()), $columnMetadata->getTypeWithAttributes());

        if (!$columnMetadata->isNullable()) {
            $sql .= ' NOT NULL';
        }

        if ($columnMetadata->getDefaultValue() !== null) {
            $sql .= sprintf(' DEFAULT %s', $columnMetadata->getDefaultValuePrintable());
        }

        return $sql;
    }

    public function dropColumn(string $tableName, ColumnMetadata $columnMetadata): string
    {
        if (!$this->supportsDropColumn()) {
            throw new LogicException(sprintf("The method '%s' is not supported with SQLite versions older than 3.35.0.", __METHOD__));
        }
        return sprintf('ALTER TABLE %s DROP COLUMN %s', $this->quote($tableName), $this->quote($columnMetadata->getName()));
    }

    public function renameColumn(string $tableName, string $oldColumnName, string $newColumnName): string
    {
        return sprintf('ALTER TABLE %s RENAME COLUMN %s to %s', $this->quote($tableName), $this->quote($oldColumnName), $this->quote($newColumnName));
    }

    public function modifyColumn(string $tableName, ColumnMetadata $columnMetadata): string
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the schema interface.", __METHOD__));
    }

    /**
     * @param IndexMetadata $indexMetadata
     * @return string
     */
    public function createIndex(IndexMetadata $indexMetadata): string
    {
        $sql = sprintf('CREATE INDEX %s ON %s (%s)',
            $indexMetadata->getName(),
            $this->quote($indexMetadata->getTableName()),
            implode(', ', $this->quotes($indexMetadata->getColumns()))
        );

        if ($indexMetadata->isUnique()) {
            $sql = str_replace('CREATE INDEX', 'CREATE UNIQUE INDEX', $sql);
        }

        return $sql;
    }

    public function dropIndex(IndexMetadata $indexMetadata): string
    {
        return sprintf('DROP INDEX %s;', $indexMetadata->getName());
    }

    public function getDateTimeFormatString(): string
    {
        return 'Y-m-d H:i:s';
    }

    public function getDateFormatString(): string
    {
        return 'Y-m-d';
    }

    private function foreignKeyConstraints(ForeignKeyMetadata $foreignKey): string
    {
        $referencedTable = $foreignKey->getReferenceTable();
        $referencedColumns = $foreignKey->getReferenceColumns();
        $sql = [];
        $sql[] = sprintf('FOREIGN KEY (%s) REFERENCES %s (%s)',
            implode(', ', $this->quotes($foreignKey->getColumns())),
            $this->quote($referencedTable),
            implode(', ', $referencedColumns)
        );

        switch ($foreignKey->getOnDelete()) {
            case ForeignKeyMetadata::RESTRICT:
                $sql[] = 'ON DELETE RESTRICT';
                break;
            case ForeignKeyMetadata::CASCADE:
                $sql[] = 'ON DELETE CASCADE';
                break;
            case ForeignKeyMetadata::SET_DEFAULT:
                $sql[] = 'ON DELETE SET DEFAULT';
                break;
            case ForeignKeyMetadata::SET_NULL:
                $sql[] = 'ON DELETE SET NULL';
                break;
            case ForeignKeyMetadata::NO_ACTION:
                $sql[] = 'ON DELETE NO ACTION';
                break;
        }

        switch ($foreignKey->getOnUpdate()) {
            case ForeignKeyMetadata::RESTRICT:
                $sql[] = 'ON UPDATE RESTRICT';
                break;
            case ForeignKeyMetadata::CASCADE:
                $sql[] = 'ON UPDATE CASCADE';
                break;
            case ForeignKeyMetadata::SET_DEFAULT:
                $sql[] = 'ON UPDATE SET DEFAULT';
                break;
            case ForeignKeyMetadata::SET_NULL:
                $sql[] = 'ON UPDATE SET NULL';
                break;
            case ForeignKeyMetadata::NO_ACTION:
                $sql[] = 'ON UPDATE NO ACTION';
                break;
        }

        return implode(' ', $sql);
    }

    public function supportsForeignKeyConstraints(): bool
    {
        return true;
    }

    public function supportsIndexes(): bool
    {
        return true;
    }

    public function supportsTransactions(): bool
    {
        return true;
    }

    public function supportsDropColumn(): bool
    {
        return SQLite3::version()['versionString'] >= '3.35.0';
    }

    public function supportsModifyColumn(): bool
    {
        return false;
    }

    public function supportsAddForeignKey(): bool
    {
        return false;
    }


    public function supportsDropForeignKey(): bool
    {
        return false;
    }

    public function getIdentifierQuoteSymbols(): array
    {
        return ['`', '`'];
    }
}
