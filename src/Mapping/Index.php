<?php

namespace Michel\PaperORM\Mapping;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class Index
{
    private array $columns;

    private bool $unique = false;
    private ?string $name;
    public function __construct(array $columns, bool $unique = false, string $name = null)
    {
        $this->columns = $columns;
        $this->unique = $unique;
        $this->name = $name;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function setColumns(array $columns): Index
    {
        $this->columns = $columns;
        return $this;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function setUnique(bool $unique): Index
    {
        $this->unique = $unique;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): Index
    {
        $this->name = $name;
        return $this;
    }
}
