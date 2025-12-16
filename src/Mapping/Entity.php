<?php

namespace Michel\PaperORM\Mapping;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Entity
{
    private string $table;
    private ?string $repositoryClass = null;

    public function __construct( string $table, ?string $repository = null)
    {
        $this->table = trim($table, '`');
        $this->repositoryClass = $repository;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getRepositoryClass(): ?string
    {
        return $this->repositoryClass;
    }

}
