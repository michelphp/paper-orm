<?php

declare(strict_types=1);

/**
 * PaperORM read path, layer by layer: where does the time go?
 *
 *   php profile.php [--n=1000] [--posts=5] [--iterations=10]
 *
 * Every step is measured from the same state: the entity manager is cleared and the garbage collector run
 * before each measurement, so what was loaded by a previous step cannot slow down the next one.
 * "Cost" is the difference with the step named in the "over" column.
 * Runs PaperORM only, on in-memory SQLite, median of --iterations runs after one warm-up.
 */

require __DIR__ . '/vendor/autoload.php';

use Bench\Entity\Paper\Post;
use Bench\Entity\Paper\User;
use Bench\Schema;
use Michel\PaperORM\EntityManager;
use Michel\PaperORM\Expression\Expr;
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

printf("PaperORM read path profile — PHP %s, median of %d runs\n\n", PHP_VERSION, $iterations);

$cases = [
    sprintf('users (%d rows)', $n) => static function () use ($em): QueryBuilder {
        return $em->getRepository(User::class)->qb();
    },
    sprintf('users + posts (%d rows)', $n * max(1, $posts)) => static function () use ($em): QueryBuilder {
        return $em->getRepository(User::class)->qb()->leftJoin(User::class, Post::class, 'posts');
    },
];

foreach ($cases as $label => $queryBuilder) {
    $sql = $queryBuilder()->buildSqlQuery()->getQuery();
    profile($label, $em, $iterations, [
        ['raw SQL (PDO fetchAll)', null, static function () use ($pdo, $sql) {
            return $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        }],
        ['JoinQL / GraphBuilder', 'raw SQL (PDO fetchAll)', static function () use ($queryBuilder) {
            return $queryBuilder()->buildSqlQuery()->getResult();
        }],
        ['arrays', 'JoinQL / GraphBuilder', static function () use ($queryBuilder) {
            return $queryBuilder()->getResult(QueryBuilder::HYDRATE_ARRAY);
        }],
        ['read-only objects', 'JoinQL / GraphBuilder', static function () use ($queryBuilder) {
            return $queryBuilder()->getResult(QueryBuilder::HYDRATE_OBJECT_READONLY);
        }],
        ['managed objects (proxy)', 'JoinQL / GraphBuilder', static function () use ($queryBuilder) {
            return $queryBuilder()->getResult(QueryBuilder::HYDRATE_OBJECT);
        }],
    ]);
}

// find($id): the fixed cost of one query matters more than hydration.
$ids = range(1, $n);
$byId = static function (int $id) use ($em): QueryBuilder {
    $qb = $em->getRepository(User::class)->qb();
    $qb->where(Expr::equal('id', $id)->toPrepared($qb->getPrimaryAlias()));
    return $qb->setParams(['id' => $id]);
};
$sql = $byId(1)->buildSqlQuery()->getQuery();
profile(sprintf('find($id) x %d', $n), $em, $iterations, [
    ['raw SQL (PDO prepare+execute)', null, static function () use ($pdo, $sql, $ids) {
        foreach ($ids as $id) {
            $statement = $pdo->prepare($sql);
            $statement->bindValue(':id', $id);
            $statement->execute();
            $statement->fetch(\PDO::FETCH_ASSOC);
        }
    }],
    ['build SQL (QueryBuilder, JoinQL)', null, static function () use ($byId, $ids) {
        foreach ($ids as $id) {
            $byId($id)->buildSqlQuery()->getQuery();
        }
    }],
    ['execute (JoinQL, graph)', 'build SQL (QueryBuilder, JoinQL)', static function () use ($byId, $ids) {
        foreach ($ids as $id) {
            $byId($id)->buildSqlQuery()->getOneOrNullResult();
        }
    }],
    ['managed object', 'execute (JoinQL, graph)', static function () use ($byId, $ids) {
        foreach ($ids as $id) {
            $byId($id)->getOneOrNullResult(QueryBuilder::HYDRATE_OBJECT);
        }
    }],
    ['Repository::find()->toObject()', 'managed object', static function () use ($em, $ids) {
        foreach ($ids as $id) {
            $em->getRepository(User::class)->find($id)->toObject();
        }
    }],
]);

/**
 * @param array<int, array{0: string, 1: ?string, 2: callable}> $steps label, label of the step it is compared to, callback
 */
function profile(string $title, EntityManager $em, int $iterations, array $steps): void
{
    printf("%s\n%-32s %11s %11s   %s\n", $title, 'Step', 'Total', 'Cost', 'over');
    echo str_repeat('-', 90) . "\n";
    // Interleaved like bench.php: every iteration runs every step, in a rotating order, so a change of speed
    // of the machine during the run affects all steps alike instead of a whole step.
    $times = [];
    $count = count($steps);
    for ($i = 0; $i <= $iterations; $i++) {
        for ($k = 0; $k < $count; $k++) {
            [$label, , $callback] = $steps[($i + $k) % $count];
            $em->clear();
            gc_collect_cycles();
            $start = hrtime(true);
            $callback();
            if ($i > 0) {
                $times[$label][] = (hrtime(true) - $start) / 1e6;
            }
        }
    }

    $medians = [];
    foreach ($steps as [$label, $over]) {
        sort($times[$label]);
        $medians[$label] = $times[$label][intdiv(count($times[$label]), 2)];
        $cost = $over === null ? $medians[$label] : $medians[$label] - $medians[$over];
        printf("%-32s %8.2f ms %8.2f ms   %s\n", $label, $medians[$label], $cost, $over ?? '-');
    }
    echo "\n";
}
