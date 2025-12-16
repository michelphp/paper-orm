<?php

namespace Michel\PaperORM\Metadata;

use Michel\PaperORM\Mapping\Column\JoinColumn;

final class IndexMetadata
{
    private string $tableName;
    private ?string $name;
    private array $columns;
    private bool $unique;

    public function __construct(string $tableName, ?string $name, array $columns, bool $unique = false)
    {
        $this->tableName = $tableName;
        $this->name = strtoupper($name);
        $this->columns = $columns;
        $this->unique = $unique;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['tableName'],
            $data['name'],
            $data['columns'],
            $data['unique']
        );
    }

    public function toArray(): array
    {
        return [
            'tableName' => $this->getTableName(),
            'name' => $this->getName(),
            'columns' => $this->getColumns(),
            'unique' => $this->isUnique()
        ];
    }
}
