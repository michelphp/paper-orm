<?php

declare(strict_types=1);

namespace Bench\Adapter;

interface OrmAdapter
{
    public function label(): string;

    public function version(): string;

    /**
     * Builds the entity manager. Must not touch the database schema.
     */
    public function boot(): void;

    public function pdo(): \PDO;

    /**
     * Loads every metadata used by the scenarios, then clears the identity map.
     */
    public function warmUp(): void;

    public function clear(): void;

    public function insertUsers(int $count): void;

    public function findById(int $id): ?object;

    public function findAll(): array;

    public function findAllAsArray(): array;

    public function findAllWithPosts(): array;

    public function countPosts(object $user): int;

    public function paginate(int $offset, int $limit): array;

    /**
     * Must return $limit complete users (all their posts), not $limit SQL rows.
     */
    public function paginateWithPosts(int $offset, int $limit): array;

    public function saveAll(array $users): void;

    public function removeAll(array $users): void;
}
