<?php

namespace Michel\PaperORM\Manager;

use Michel\Lock\Handler\FlockHandler;
use Michel\Lock\Locker;

final class PaperSequenceManager
{
    private PaperKeyValueManager $keyValueManager;
    private Locker $locker;
    private array $cache = [];

    /**
     * Sequence keys locked by this manager, by lock key.
     *
     * @var array<string, string>
     */
    private array $locked = [];

    public function __construct(PaperKeyValueManager $keyValueManager, ?Locker $locker = null)
    {
        $this->keyValueManager = $keyValueManager;
        $this->locker = $locker ?? new Locker(new FlockHandler());
    }

    public function peek(string $key): int
    {
        $key = strtolower($key);
        $next =  $this->getNext($key);
        $this->cache[$key] = $next;
        return $next;
    }

    /**
     * Reserves the sequence for this process until increment() or unlockAll(): another process that locks it
     * waits, so it cannot peek the same value. Locking a sequence twice is a no-op.
     */
    public function lock(string $key): void
    {
        $lockKey = $this->resolveLockKey(strtolower($key));
        if (isset($this->locked[$lockKey])) {
            return;
        }
        if (!$this->locker->lock($lockKey, true)) {
            throw new \RuntimeException(sprintf('Could not lock the sequence "%s".', $key));
        }
        $this->locked[$lockKey] = $key;
    }

    public function increment(string $key): void
    {
        $key = strtolower($key);
        // Not locked by a caller: lock it just for this read and write.
        $this->lock($key);
        try {
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
        } finally {
            $this->unlock($key);
        }
    }

    /**
     * Releases every sequence still locked, e.g. after an insert that failed before increment().
     */
    public function unlockAll(): void
    {
        foreach ($this->locked as $key) {
            $this->unlock($key);
            unset($this->cache[$key]);
        }
    }

    public function reset(string $key): void
    {
        $this->keyValueManager->set($this->resolveKey($key), 0);
    }

    private function unlock(string $key): void
    {
        $lockKey = $this->resolveLockKey(strtolower($key));
        if (!isset($this->locked[$lockKey])) {
            return;
        }
        unset($this->locked[$lockKey]);
        $this->locker->unlock($lockKey);
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

    private function resolveLockKey(string $key): string
    {
        // A lock key only accepts letters, digits, "_" and "."; a sequence key may hold any character.
        return 'paper_sequence.' . md5($key);
    }
}
