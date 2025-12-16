<?php

namespace Michel\PaperORM\Cache;


use Michel\PaperORM\Mapping\Column\PrimaryKeyColumn;

final class PrimaryKeyColumnCache
{
    private static ?PrimaryKeyColumnCache $instance = null;
    private array $data = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function set(string $key, PrimaryKeyColumn $primaryKeyColumn)
    {
        $this->data[$key] = $primaryKeyColumn;
    }

    public function get(string $key): ?PrimaryKeyColumn
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return null;
    }
}
