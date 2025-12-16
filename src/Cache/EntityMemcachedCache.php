<?php

namespace Michel\PaperORM\Cache;


final class EntityMemcachedCache
{
    /**
     * @var array<object>
     */
    private array $cache = [];

    public function get(string $class, string $primaryKeyValue): ?object
    {
        $key = $this->generateKey($class, $primaryKeyValue);
        if ($this->has($key)) {
            return $this->cache[$key];
        }
        return null;
    }

    public function has(string $key): bool
    {
        return isset($this->cache[$key]);
    }

    public function set(string $class, string $primaryKeyValue, object $value): void
    {
        $this->cache[$this->generateKey($class, $primaryKeyValue)] = $value;
    }

    public function invalidate(string $class, string $primaryKeyValue): void
    {
        unset($this->cache[$this->generateKey($class, $primaryKeyValue)]);
    }

    public function clear(): void
    {
        $this->cache = [];
    }

    private function generateKey(string $class, string $primaryKeyValue): string
    {
        return md5($class . $primaryKeyValue);
    }
}
