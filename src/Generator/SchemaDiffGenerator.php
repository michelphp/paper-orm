<?php

namespace Michel\PaperORM\Generator;

use LogicException;
use Michel\PaperORM\Mapping\Column\Column;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Mapping\Index;
use Michel\PaperORM\Metadata\ColumnMetadata;
use Michel\PaperORM\Platform\PlatformInterface;
use Michel\PaperORM\Schema\SchemaInterface;

final class SchemaDiffGenerator
{
    private PlatformInterface $platform;

    public function __construct(PlatformInterface $platform)
    {
        $this->platform = $platform;
    }

    public function generateDiffStatements(array $tables): array
    {
        $tablesExist = $this->platform->listTables();
        $schema = $this->platform->getSchema();
        foreach ($tables as $tableName => $tableData) {
            if (!isset($tableData['columns'])) {
                throw new LogicException(sprintf(
                    "Missing column definitions for table '%s'. Each table must have a 'columns' key with its column structure.",
                    $tableName
                ));
            }
            if (!isset($tableData['indexes'])) {
                throw new LogicException(sprintf(
                    "Missing index definitions for table '%s'. Ensure the 'indexes' key is set, even if empty, to maintain consistency.",
                    $tableName
                ));
            }
        }

        list( $sqlUp, $sqlDown) = $this->diff($tables, $schema, $tablesExist);
        return [
            'up' => $sqlUp,
            'down' => $sqlDown
        ];
    }

    private function diff(array $tables, SchemaInterface $schema, array $tablesExist): array
    {
        $sqlUp = [];
        $sqlDown = [];
        $sqlForeignKeyUp = [];
        $sqlForeignKeyDown = [];
        foreach ($tables as $tableName => $tableData) {
            /**
             * @var array<Column> $columns
             * @var array<Index> $indexes
             */
            $columns = $tableData['columns'];
            $indexes = $tableData['indexes'];
            $diff = $this->platform->diff($tableName, $columns, $indexes);
            $columnsToAdd = $diff->getColumnsToAdd();
            $columnsToUpdate = $diff->getColumnsToUpdate();
            $columnsToDelete = $diff->getColumnsToDelete();

            $foreignKeyToAdd = $diff->getForeignKeyToAdd();
            $foreignKeyToDrop = $diff->getForeignKeyToDrop();

            $indexesToAdd = $diff->getIndexesToAdd();
            $indexesToUpdate = $diff->getIndexesToUpdate();
            $indexesToDelete = $diff->getIndexesToDelete();

            if (!in_array($tableName, $tablesExist)) {
                $sqlUp[] = $schema->createTable($tableName, $columnsToAdd);
                foreach ($indexesToAdd as $index) {
                    $sqlUp[] = $schema->createIndex($index);
                    $sqlDown[] = $schema->dropIndex($index);
                }

                foreach ($foreignKeyToAdd as $foreignKey) {
                    if ($schema->supportsAddForeignKey()) {
                        $sqlForeignKeyUp[] = $schema->createForeignKeyConstraint($tableName, $foreignKey);
                    }
                    if ($schema->supportsDropForeignKey()) {
                        $sqlForeignKeyDown[] = $schema->dropForeignKeyConstraints($tableName, $foreignKey->getName());
                    }
                }

                $sqlDown[] = $schema->dropTable($tableName);
                continue;
            }

            foreach ($columnsToAdd as $column) {
                $sqlUp[] = $schema->addColumn($tableName, $column);
                if ($schema->supportsDropColumn()) {
                    $sqlDown[] = $schema->dropColumn($tableName, $column);
                } else {
                    $sqlDown[] = sprintf(
                        '-- Drop column %s is not supported with %s. You might need to manually drop the column.',
                        $column->getName(),
                        get_class($schema)
                    );
                }
            }


            foreach ($indexesToDelete as $index) {
                $sqlUp[] = $schema->dropIndex($index);
                $sqlDown[] = $schema->createIndex($diff->getOriginalIndex($index->getName()));
            }
            foreach ($indexesToAdd as $index) {
                $sqlUp[] = $schema->createIndex($index);
                $sqlDown[] = $schema->dropIndex($index);
            }

            foreach ($foreignKeyToAdd as $foreignKey) {
                if ($schema->supportsAddForeignKey()) {
                    $sqlUp[] = $schema->createForeignKeyConstraint($tableName, $foreignKey);
                }
                if ($schema->supportsDropForeignKey()) {
                    $sqlDown[] = $schema->dropForeignKeyConstraints($tableName, $foreignKey->getName());
                }
            }

            foreach ($columnsToUpdate as $column) {
                if ($schema->supportsModifyColumn()) {
                    $sqlUp[] = $schema->modifyColumn($tableName, $column);
                    $sqlDown[] = $schema->modifyColumn($tableName, $diff->getOriginalColumn($column->getName()));
                } else {
                    $sqlUp[] = sprintf(
                        '-- Modify column %s is not supported with %s. Consider creating a new column and migrating the data.',
                        $column->getName(),
                        get_class($schema)
                    );
                }
            }

            foreach ($columnsToDelete as $column) {
                if ($schema->supportsDropColumn()) {
                    $sqlUp[] = $schema->dropColumn($tableName, $column);
                    $sqlDown[] = $schema->addColumn($tableName, $diff->getOriginalColumn($column->getName()));
                } else {
                    $sqlUp[] = sprintf(
                        '-- Drop column %s is not supported with %s. Consider manually handling this operation.',
                        $column->getName(),
                        get_class($schema)
                    );
                }
            }

            foreach ($indexesToUpdate as $index) {
                $sqlUp[] = $schema->dropIndex($diff->getOriginalIndex($index->getName()));
                $sqlUp[] = $schema->createIndex($index);

                $sqlDown[] = $schema->dropIndex($index);
                $sqlDown[] = $schema->createIndex($diff->getOriginalIndex($index->getName()));
            }

            foreach ($foreignKeyToDrop as $foreignKey) {
                if ($schema->supportsDropForeignKey()) {
                    $sqlForeignKeyUp[] = $schema->dropForeignKeyConstraints($tableName, $foreignKey->getName());
                }

                if ($schema->supportsAddForeignKey()) {
                    $sqlForeignKeyDown[] = $schema->createForeignKeyConstraint($tableName, $diff->getOriginalForeignKey($foreignKey->getName()));
                }
            }
        }

        $sqlUp = array_merge($sqlUp, $sqlForeignKeyUp);
        $sqlDown = array_merge($sqlForeignKeyDown, $sqlDown);
        return [$sqlUp, $sqlDown];
    }
}
