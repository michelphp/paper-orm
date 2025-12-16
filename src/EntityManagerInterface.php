<?php

namespace Michel\PaperORM;

use Michel\PaperORM\Cache\EntityMemcachedCache;
use Michel\PaperORM\Manager\PaperKeyValueManager;
use Michel\PaperORM\Manager\PaperSequenceManager;
use Michel\PaperORM\Platform\PlatformInterface;
use Michel\PaperORM\Repository\Repository;

interface EntityManagerInterface
{
    public function persist(object $entity): void;
    public function remove(object $entity): void;
    public function flush(object $entity = null ): void;
    public function registry(): PaperKeyValueManager;
    public function sequence(): PaperSequenceManager;
    public function getRepository(string $entity): Repository;
    public function getPlatform(): PlatformInterface;
    public function getConnection(): PaperConnection;
    public function getCache(): EntityMemcachedCache;
    public function clear(): void;
}
