<?php

declare(strict_types=1);

namespace Bench;

use Bench\Adapter\Adapters;
use Bench\Adapter\OrmAdapter;

/**
 * Runs one scenario for one ORM. Each call happens in a fresh PHP process (see run.php),
 * so time and peak memory are never polluted by a previous scenario.
 */
final class Worker
{
    public const SCENARIOS = [
        'bootstrap' => 'Build the entity manager, then fetch one entity (cold start)',
        'insert' => 'persist() N new users, then a single flush()',
        'find_pk' => 'Fetch N users one by one by primary key',
        'find_all' => 'Fetch N users as objects',
        'find_all_array' => 'Fetch N users as arrays',
        'join' => 'Fetch N users with their posts (one-to-many, single query)',
        'paginate' => 'Fetch 10 pages of 20 users ordered by id',
        'paginate_join' => 'Fetch 10 pages of 20 users with all their posts, ordered by id',
        'update' => 'Modify N managed users, then a single flush()',
        'delete' => 'remove() N managed users, then a single flush()',
    ];

    private const PAGE_SIZE = 20;
    private const PAGE_COUNT = 10;

    public static function run(string $orm, string $scenario, int $n, int $postsPerUser): array
    {
        if (!isset(self::SCENARIOS[$scenario])) {
            throw new \InvalidArgumentException(sprintf('Unknown scenario "%s".', $scenario));
        }

        $adapter = Adapters::create($orm);
        if ($scenario === 'bootstrap') {
            return self::bootstrap($adapter);
        }

        $adapter->boot();
        $pdo = $adapter->pdo();
        Schema::create($pdo);
        $adapter->warmUp();

        if ($scenario !== 'insert') {
            $withPosts = $scenario === 'join' || $scenario === 'paginate_join';
            Schema::seed($pdo, $n, $withPosts ? $postsPerUser : 0);
        }

        $users = [];
        if ($scenario === 'update' || $scenario === 'delete') {
            // Loading is not part of the measurement, only the write is.
            $users = $adapter->findAll();
        }

        $operation = self::operation($scenario, $adapter, $n, $users);
        unset($users);

        [$result, $timeMs, $memory] = self::measure($operation);
        [$check, $expected] = self::verify($scenario, $adapter, $pdo, $result, $n, $postsPerUser);

        return self::result($timeMs, $memory, $check, $expected);
    }

    private static function bootstrap(OrmAdapter $adapter): array
    {
        self::resetMemory();
        $memoryBefore = memory_get_usage();

        $start = hrtime(true);
        $adapter->boot();
        $pdo = $adapter->pdo();
        $elapsed = hrtime(true) - $start;

        // Schema and data are not part of the measurement.
        Schema::create($pdo);
        Schema::seed($pdo, 1, 0);

        $start = hrtime(true);
        $user = $adapter->findById(1);
        $elapsed += hrtime(true) - $start;

        $memory = memory_get_peak_usage() - $memoryBefore;

        return self::result($elapsed / 1e6, $memory, $user !== null ? 1 : 0, 1);
    }

    private static function operation(string $scenario, OrmAdapter $adapter, int $n, array $users): callable
    {
        switch ($scenario) {
            case 'insert':
                return static function () use ($adapter, $n) {
                    $adapter->insertUsers($n);
                };
            case 'find_pk':
                return static function () use ($adapter, $n) {
                    $found = 0;
                    for ($id = 1; $id <= $n; $id++) {
                        if ($adapter->findById($id) !== null) {
                            $found++;
                        }
                    }
                    return $found;
                };
            case 'find_all':
                return static function () use ($adapter) {
                    return $adapter->findAll();
                };
            case 'find_all_array':
                return static function () use ($adapter) {
                    return $adapter->findAllAsArray();
                };
            case 'join':
                return static function () use ($adapter) {
                    return $adapter->findAllWithPosts();
                };
            case 'paginate':
                return static function () use ($adapter, $n) {
                    $ids = [];
                    foreach (self::pageOffsets($n) as $offset) {
                        foreach ($adapter->paginate($offset, self::PAGE_SIZE) as $user) {
                            $ids[] = $user->getId();
                        }
                    }
                    return $ids;
                };
            case 'paginate_join':
                return static function () use ($adapter, $n) {
                    $users = [];
                    foreach (self::pageOffsets($n) as $offset) {
                        foreach ($adapter->paginateWithPosts($offset, self::PAGE_SIZE) as $user) {
                            $users[] = $user;
                        }
                    }
                    return $users;
                };
            case 'update':
                return static function () use ($adapter, $users) {
                    foreach ($users as $user) {
                        $user->setAge($user->getAge() + Schema::AGE_INCREMENT);
                    }
                    $adapter->saveAll($users);
                };
            case 'delete':
                return static function () use ($adapter, $users) {
                    $adapter->removeAll($users);
                };
        }

        throw new \LogicException(sprintf('No operation defined for scenario "%s".', $scenario));
    }

    /**
     * Checks that both ORMs really did the same work, otherwise the timing means nothing.
     *
     * @return array{0: int|string, 1: int|string}
     */
    private static function verify(string $scenario, OrmAdapter $adapter, \PDO $pdo, $result, int $n, int $postsPerUser): array
    {
        switch ($scenario) {
            case 'insert':
                return [Schema::count($pdo, 'SELECT COUNT(*) FROM users'), $n];
            case 'find_pk':
                return [$result, $n];
            case 'find_all':
            case 'find_all_array':
                return [count($result), $n];
            case 'join':
                $posts = 0;
                foreach ($result as $user) {
                    $posts += $adapter->countPosts($user);
                }
                return [
                    sprintf('%d users / %d posts', count($result), $posts),
                    sprintf('%d users / %d posts', $n, $n * $postsPerUser),
                ];
            case 'paginate':
                return [array_sum($result), self::expectedPageIdSum($n)];
            case 'paginate_join':
                $ids = 0;
                $posts = 0;
                foreach ($result as $user) {
                    $ids += $user->getId();
                    $posts += $adapter->countPosts($user);
                }
                $expectedUsers = count(self::pageOffsets($n)) * min(self::PAGE_SIZE, $n);
                return [
                    sprintf('%d users / id sum %d / %d posts', count($result), $ids, $posts),
                    sprintf('%d users / id sum %d / %d posts', $expectedUsers, self::expectedPageIdSum($n), $expectedUsers * $postsPerUser),
                ];
            case 'update':
                $sql = sprintf('SELECT COUNT(*) FROM users WHERE age >= %d', Schema::updatedAgeThreshold());
                return [Schema::count($pdo, $sql), $n];
            case 'delete':
                return [Schema::count($pdo, 'SELECT COUNT(*) FROM users'), 0];
        }

        throw new \LogicException(sprintf('No verification defined for scenario "%s".', $scenario));
    }

    private static function expectedPageIdSum(int $n): int
    {
        $sum = 0;
        foreach (self::pageOffsets($n) as $offset) {
            $last = min($offset + self::PAGE_SIZE, $n);
            for ($id = $offset + 1; $id <= $last; $id++) {
                $sum += $id;
            }
        }
        return $sum;
    }

    /**
     * @return array<int>
     */
    private static function pageOffsets(int $n): array
    {
        if ($n <= self::PAGE_SIZE) {
            return [0];
        }

        $offsets = [];
        $maxOffset = $n - self::PAGE_SIZE;
        for ($page = 0; $page < self::PAGE_COUNT; $page++) {
            $offsets[] = intdiv($page * $maxOffset, self::PAGE_COUNT - 1);
        }
        return $offsets;
    }

    /**
     * @return array{0: mixed, 1: float, 2: int}
     */
    private static function measure(callable $operation): array
    {
        self::resetMemory();
        $memoryBefore = memory_get_usage();

        $start = hrtime(true);
        $result = $operation();
        $elapsed = hrtime(true) - $start;

        return [$result, $elapsed / 1e6, max(0, memory_get_peak_usage() - $memoryBefore)];
    }

    private static function resetMemory(): void
    {
        gc_collect_cycles();
        if (function_exists('memory_reset_peak_usage')) {
            memory_reset_peak_usage();
        }
    }

    private static function result(float $timeMs, int $memory, $check, $expected): array
    {
        return [
            'time_ms' => $timeMs,
            'memory' => $memory,
            'check' => $check,
            'expected' => $expected,
            'ok' => $check === $expected,
        ];
    }
}
