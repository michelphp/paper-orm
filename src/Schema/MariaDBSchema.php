<?php

namespace Michel\PaperORM\Schema;

use LogicException;
use Michel\PaperORM\Metadata\ColumnMetadata;
use Michel\PaperORM\Metadata\ForeignKeyMetadata;
use Michel\PaperORM\Metadata\IndexMetadata;
use Michel\PaperORM\Schema\Traits\IdentifierQuotingTrait;

class MariaDBSchema implements SchemaInterface
{

    use IdentifierQuotingTrait;
    public function showDatabases(): string
    {
        return "SHOW DATABASES";
    }

    public function showTables(): string
    {
        return "SHOW TABLES";
    }

    public function showTableColumns(string $tableName): string
    {
        return sprintf("SHOW COLUMNS FROM %s", $this->quote($tableName));
    }

    public function showForeignKeys(string $tableName): string
    {
        return trim(sprintf(
            <<<SQL
            SELECT DISTINCT
                k.CONSTRAINT_NAME,
                k.COLUMN_NAME,
                k.REFERENCED_TABLE_NAME,
                k.REFERENCED_COLUMN_NAME,
                k.ORDINAL_POSITION /*!50116,
                c.UPDATE_RULE,
                c.DELETE_RULE */
            FROM information_schema.key_column_usage k /*!50116
            INNER JOIN information_schema.referential_constraints c
                ON c.CONSTRAINT_NAME = k.CONSTRAINT_NAME
                AND c.TABLE_NAME = k.TABLE_NAME */
            WHERE k.TABLE_SCHEMA = DATABASE()
              AND k.TABLE_NAME = "%s"
              AND k.REFERENCED_COLUMN_NAME IS NOT NULL
              /*!50116 AND c.CONSTRAINT_SCHEMA = DATABASE() */
            ORDER BY k.ORDINAL_POSITION
            SQL,
            $tableName
        ));
    }

    public function showTableIndexes(string $tableName): string
    {
        return sprintf('SHOW INDEXES FROM %s', $this->quote($tableName));
    }

    public function createDatabase(string $databaseName): string
    {
        return sprintf('CREATE DATABASE %s', $databaseName);
    }

    public function createDatabaseIfNotExists(string $databaseName): string
    {
        return sprintf('CREATE DATABASE IF NOT EXISTS %s', $databaseName);
    }

    public function dropDatabase(string $databaseName): string
    {
        return sprintf('DROP DATABASE %s', $databaseName);
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
        foreach ($columns as $columnMetadata) {
            $line = sprintf('%s %s', $this->quote($columnMetadata->getName()), $columnMetadata->getTypeWithAttributes());
            if ($columnMetadata->isPrimary()) {
                $line .= ' AUTO_INCREMENT PRIMARY KEY NOT NULL';
            } else {
                if (!$columnMetadata->isNullable()) {
                    $line .= ' NOT NULL';
                }
                if ($columnMetadata->getDefaultValue() !== null) {
                    $line .= sprintf(' DEFAULT %s', $columnMetadata->getDefaultValuePrintable());
                } elseif ($columnMetadata->isNullable()) {
                    $line .= ' DEFAULT NULL';
                }
            }

            $lines[] = $line;
        }


        $linesString = implode(',', $lines);
        $createTable = sprintf("CREATE TABLE %s (%s)", $this->quote($tableName), $linesString);

        $indexesSql = [];
        $options['indexes'] = $options['indexes'] ?? [];
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
        $sql = [];

        if (empty($foreignKey->getName())) {
            throw new LogicException(sprintf('The foreign key name can not be empty : table %s, columns %s', $tableName, implode(', ', $foreignKey->getColumns())));
        }

        $sql[] = sprintf('ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s(%s)',
            $this->quote($tableName),
            $foreignKey->getName(),
            implode(', ',  $this->quotes($foreignKey->getColumns())),
            $this->quote($foreignKey->getReferenceTable()),
            implode(', ',  $this->quotes($foreignKey->getReferenceColumns())),
        );

        switch ($foreignKey->getOnDelete()) {
            case ForeignKeyMetadata::RESTRICT:
                $sql[] = 'ON DELETE RESTRICT';
                break;
            case ForeignKeyMetadata::CASCADE:
                $sql[] = 'ON DELETE CASCADE';
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
            case ForeignKeyMetadata::SET_NULL:
                $sql[] = 'ON UPDATE SET NULL';
                break;
            case ForeignKeyMetadata::NO_ACTION:
                $sql[] = 'ON UPDATE NO ACTION';
                break;
        }

        return implode(' ', $sql) . ';';
    }

    public function dropForeignKeyConstraints(string $tableName, string $foreignKeyName): string
    {
        return sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $this->quote($tableName), $foreignKeyName);
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
        $sql = sprintf('ALTER TABLE %s ADD COLUMN %s %s', $this->quote($tableName), $this->quote($columnMetadata->getName()), $columnMetadata->getTypeWithAttributes());
        if (!$columnMetadata->isNullable()) {
            $sql .= ' NOT NULL';
        }

        if ($columnMetadata->getDefaultValue() !== null) {
            $sql .= sprintf(' DEFAULT %s', $columnMetadata->getDefaultValuePrintable());
        } elseif ($columnMetadata->isNullable()) {
            $sql .= ' DEFAULT NULL';
        }

        return $sql;
    }

    public function dropColumn(string $tableName, ColumnMetadata $columnMetadata): string
    {
        return sprintf('ALTER TABLE %s DROP COLUMN %s', $this->quote($tableName), $this->quote($columnMetadata->getName()));
    }

    public function renameColumn(string $tableName, string $oldColumnName, string $newColumnName): string
    {
        return sprintf('ALTER TABLE %s RENAME COLUMN %s to %s', $this->quote($tableName), $this->quote($oldColumnName), $this->quote($newColumnName));
    }

    public function modifyColumn(string $tableName, ColumnMetadata $columnMetadata): string
    {
        $sql = $this->addColumn($tableName, $columnMetadata);
        return str_replace('ADD COLUMN', 'MODIFY COLUMN', $sql);
    }

    /**
     * @param IndexMetadata $indexMetadata
     * @return string
     */
    public function createIndex(IndexMetadata $indexMetadata): string
    {
        $indexType = $indexMetadata->isUnique() ? 'CREATE UNIQUE INDEX' : 'CREATE INDEX';
        return sprintf('%s %s ON %s (%s)',
            $indexType,
            $indexMetadata->getName(),
            $this->quote($indexMetadata->getTableName()),
            implode(', ', $this->quotes($indexMetadata->getColumns())),
        );
    }

    public function dropIndex(IndexMetadata $indexMetadata): string
    {
        return sprintf('DROP INDEX %s ON %s;', $indexMetadata->getName(), $this->quote($indexMetadata->getTableName()));
    }


    public function getDateTimeFormatString(): string
    {
        return 'Y-m-d H:i:s';
    }

    public function getDateFormatString(): string
    {
        return 'Y-m-d';
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
        return true;
    }

    public function supportsModifyColumn(): bool
    {
        return true;
    }

    public function supportsAddForeignKey(): bool
    {
        return true;
    }

    public function supportsDropForeignKey(): bool
    {
        return true;
    }

    public function getIdentifierQuoteSymbols(): array
    {
        return ['`', '`'];
    }

}
