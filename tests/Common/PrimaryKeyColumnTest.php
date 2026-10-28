<?php

namespace Test\Michel\PaperORM\Common;

use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Entity\TableMetadataInterface;
use Michel\PaperORM\Mapper\ColumnMapper;
use Michel\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Michel\PaperORM\Mapping\Column\StringColumn;
use Michel\UniTester\TestCase;

// The test class comes first: the test finder only reads the first class of a file.
class PrimaryKeyColumnTest extends TestCase
{
    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }

    protected function execute(): void
    {
        $this->testPrimaryKeyDeclaredLast();
        $this->testInheritedPrimaryKey();
    }

    private function testPrimaryKeyDeclaredLast(): void
    {
        $column = ColumnMapper::getPrimaryKeyColumn(PrimaryKeyLastEntity::class);
        $this->assertInstanceOf(PrimaryKeyColumn::class, $column);
        $this->assertStrictEquals('id', $column->getProperty());
    }

    private function testInheritedPrimaryKey(): void
    {
        $column = ColumnMapper::getPrimaryKeyColumn(PrimaryKeyInheritedEntity::class);
        $this->assertInstanceOf(PrimaryKeyColumn::class, $column);
        $this->assertStrictEquals('id', $column->getProperty());
    }
}

// The primary key is declared after the other columns.
class PrimaryKeyLastEntity implements EntityInterface, TableMetadataInterface
{
    private ?string $name = null;
    private ?int $id = null;

    static public function getTableName(): string
    {
        return 'primary_key_last';
    }

    static public function getIndexes(): array
    {
        return [];
    }

    static public function getRepositoryName(): ?string
    {
        return null;
    }

    static public function columnsMapping(): array
    {
        return [
            (new StringColumn())->bindProperty('name'),
            (new PrimaryKeyColumn())->bindProperty('id'),
        ];
    }

    public function getPrimaryKeyValue(): ?int
    {
        return $this->id;
    }
}

abstract class PrimaryKeyBaseEntity implements EntityInterface
{
    #[PrimaryKeyColumn]
    private ?int $id = null;

    public function getPrimaryKeyValue(): ?int
    {
        return $this->id;
    }
}

// The properties of the class come before the ones of its parent: the inherited id is the last column.
class PrimaryKeyInheritedEntity extends PrimaryKeyBaseEntity
{
    #[StringColumn]
    private ?string $name = null;
}
