<?php

namespace Michel\PaperORM\Cache;


use Michel\PaperORM\Mapping\Column\Column;

final class ColumnCache
{
    private static ?ColumnCache $instance = null;
    private array $data = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function set(string $key, array $columns)
    {
        foreach ($columns as $column) {
            if (!$column instanceof Column) {
                throw new \InvalidArgumentException('All values in the array must be instances of Column.');
            }
        }

        $this->data[$key] = $columns;
    }

    public function get(string $key): array
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return [];
    }
}
