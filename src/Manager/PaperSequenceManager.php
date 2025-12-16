<?php

namespace Michel\PaperORM\Manager;

use Michel\PaperORM\Entity\EntityInterface;

final class PaperSequenceManager
{
    private PaperKeyValueManager $keyValueManager;
    private array $cache = [];

    public function __construct(PaperKeyValueManager $keyValueManager)
    {
        $this->keyValueManager = $keyValueManager;
    }

    public function peek(string $key): int
    {
        $key = strtolower($key);
        $next =  $this->getNext($key);
        $this->cache[$key] = $next;
        return $next;
    }
    public function increment(string $key): void
    {
        $key = strtolower($key);
        $cached = $this->cache[$key] ?? null;
        $expectedNext = $this->getNext($key);
        if ($cached !== null && $cached !== $expectedNext) {
            throw new \RuntimeException(sprintf(
                'Sequence conflict for key "%s": expected next %d but found %d in storage.',
                $key,
                $cached,
                $expectedNext
            ));
        }

        $this->keyValueManager->set($this->resolveKey($key), $expectedNext);
        unset($this->cache[$key]);
    }

    public function reset(string $key): void
    {
        $this->keyValueManager->set($this->resolveKey($key), 0);
    }

    private function getNext(string $key) : int
    {
        $value = $this->keyValueManager->get($this->resolveKey($key));
        return $value ? (int)$value + 1 : 1;
    }


    private function resolveKey(string $key): string
    {
        return 'sequence.' . $key;
    }
}
