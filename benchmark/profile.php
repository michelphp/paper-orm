<?php

declare(strict_types=1);

/**
 * PaperORM read path, layer by layer: where does the time go?
 *
 *   php profile.php [--n=1000] [--posts=5] [--iterations=10]
 *
 * Each step adds one layer on top of the previous one, the "layer cost" column is the difference.
 * Runs PaperORM only, on in-memory SQLite, median of --iterations runs after one warm-up.
 */

require __DIR__ . '/vendor/autoload.php';

use Bench\Entity\Paper\Post;
use Bench\Entity\Paper\User;
use Bench\Schema;
use Michel\PaperORM\EntityManager;
use Michel\PaperORM\PaperConfiguration;
use Michel\PaperORM\Query\QueryBuilder;

$options = getopt('', ['n:', 'posts:', 'iterations:']);
$n = max(1, (int) ($options['n'] ?? 1000));
$posts = max(0, (int) ($options['posts'] ?? 5));
$iterations = max(1, (int) ($options['iterations'] ?? 10));

$em = EntityManager::createFromConfig(PaperConfiguration::fromArray([
    'driver' => 'sqlite',
    'user' => null,
    'password' => null,
    'memory' => true,
]));
$pdo = $em->getConnection()->getPdo();
Schema::create($pdo);
Schema::seed($pdo, $n, $posts);

$cases = [
    sprintf('users (%d rows)', $n) => static function () use ($em): QueryBuilder {
        return $em->getRepository(User::class)->qb();
    },
    sprintf('users + posts (%d rows)', $n * max(1, $posts)) => static function () use ($em): QueryBuilder {
        return $em->getRepository(User::class)->qb()->leftJoin(User::class, Post::class, 'posts');
    },
];

printf("PaperORM read path profile — PHP %s, median of %d runs\n\n", PHP_VERSION, $iterations);

foreach ($cases as $label => $queryBuilder) {
    $sql = $queryBuilder()->buildSqlQuery()->getQuery();
    $steps = [
        'PDO fetchAll (raw SQL)' => static function () use ($pdo, $sql) {
            return $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        },
        '+ JoinQL / GraphBuilder' => static function () use ($queryBuilder) {
            return $queryBuilder()->buildSqlQuery()->getResult();
        },
        '+ array hydration' => static function () use ($queryBuilder) {
            return $queryBuilder()->getResult(QueryBuilder::HYDRATE_ARRAY);
        },
        '+ read-only objects' => static function () use ($queryBuilder) {
            return $queryBuilder()->getResult(QueryBuilder::HYDRATE_OBJECT_READONLY);
        },
        '+ managed objects (proxy)' => static function () use ($queryBuilder, $em) {
            $em->clear();
            return $queryBuilder()->getResult(QueryBuilder::HYDRATE_OBJECT);
        },
    ];

    printf("%s\n%-28s %12s %12s\n", $label, 'Step', 'Total', 'Layer cost');
    echo str_repeat('-', 54) . "\n";
    $previous = 0.0;
    foreach ($steps as $step => $callback) {
        $callback();
        $times = [];
        for ($i = 0; $i < $iterations; $i++) {
            $start = hrtime(true);
            $callback();
            $times[] = (hrtime(true) - $start) / 1e6;
        }
        sort($times);
        $median = $times[intdiv(count($times), 2)];
        printf("%-28s %9.2f ms %9.2f ms\n", $step, $median, $median - $previous);
        $previous = $median;
    }
    echo "\n";
}
