<?php

namespace Michel\PaperORM\Metadata;

final class ForeignKeyMetadata
{
    public const NO_ACTION   = 0;
    public const RESTRICT    = 1;
    public const CASCADE     = 2;
    public const SET_NULL    = 3;
    public const SET_DEFAULT = 4;

    private array $columns;
    private string $referenceTable;
    private array $referenceColumns;
    private ?string $name;

    private int $onDelete;
    private int $onUpdate;
    public function __construct(array $columns, string $referenceTable, array $referenceColumns, int $onDelete = self::NO_ACTION, int $onUpdate = self::NO_ACTION, ?string $name = null)
    {
        $this->columns = $columns;
        $this->referenceTable = $referenceTable;
        $this->referenceColumns = $referenceColumns;
        $this->name = $name;
        $this->onDelete = $onDelete;
        $this->onUpdate = $onUpdate;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getReferenceTable(): string
    {
        return $this->referenceTable;
    }

    public function getReferenceColumns(): array
    {
        return $this->referenceColumns;
    }


    public function getName(): ?string
    {
        return $this->name;
    }

    public function getOnDelete(): int
    {
        return $this->onDelete;
    }

    public function getOnUpdate(): int
    {
        return $this->onUpdate;
    }

    public static function fromArray(array $data): ForeignKeyMetadata
    {
        return new ForeignKeyMetadata($data['columns'], $data['referenceTable'], $data['referenceColumns'], $data['onDelete'] ?? self::NO_ACTION, $data['onUpdate'] ?? self::NO_ACTION, $data['name'] ?? null);
    }

    public static function fromForeignKeyMetadataOverrideName(ForeignKeyMetadata $foreignKey, string $name): ForeignKeyMetadata
    {
        return new ForeignKeyMetadata($foreignKey->getColumns(), $foreignKey->getReferenceTable(), $foreignKey->getReferenceColumns(),$foreignKey->getOnDelete(), $foreignKey->getOnUpdate(), $name);
    }

    public function toArray() : array
    {
        return [
            'name' => $this->getName(),
            'columns' => $this->getColumns(),
            'referenceTable' => $this->getReferenceTable(),
            'referenceColumns' => $this->getReferenceColumns(),
            'onDelete' => $this->getOnDelete(),
            'onUpdate' => $this->getOnUpdate(),
        ];
    }
}
