<?php

namespace Michel\PaperORM\Cache;


use Michel\PaperORM\Mapping\Column\Column;
use Michel\PaperORM\Mapping\Index;

final class IndexCache
{
    private static ?IndexCache $instance = null;
    private array $data = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function set(string $key, array $indexes)
    {
        foreach ($indexes as $index) {
            if (!$index instanceof Index) {
                throw new \InvalidArgumentException(sprintf('All values in the array must be instances of %s.', Index::class));
            }
        }

        $this->data[$key] = $indexes;
    }

    public function get(string $key): array
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return [];
    }
}
