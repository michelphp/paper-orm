<?php

declare(strict_types=1);

/**
 * Runs only PaperORM find($id), to profile its call path function by function with SPX:
 *
 *   SPX_ENABLED=1 SPX_REPORT=flat SPX_FP_LIMIT=30 php -d extension=spx.so profile-find.php [--n=1000] [--rounds=20]
 *
 * Setup (schema, data, metadata warm-up) is part of the profile but small next to n x rounds finds.
 */

require __DIR__ . '/vendor/autoload.php';

use Bench\Entity\Paper\User;
use Bench\Schema;
use Michel\PaperORM\EntityManager;
use Michel\PaperORM\PaperConfiguration;

$options = getopt('', ['n:', 'rounds:']);
$n = max(1, (int) ($options['n'] ?? 1000));
$rounds = max(1, (int) ($options['rounds'] ?? 20));

$em = EntityManager::createFromConfig(PaperConfiguration::fromArray([
    'driver' => 'sqlite',
    'user' => null,
    'password' => null,
    'memory' => true,
]));
$pdo = $em->getConnection()->getPdo();
Schema::create($pdo);
Schema::seed($pdo, $n, 0);
$em->getRepository(User::class)->find(1)->toObject();

$start = hrtime(true);
for ($round = 0; $round < $rounds; $round++) {
    $em->clear();
    for ($id = 1; $id <= $n; $id++) {
        $em->getRepository(User::class)->find($id)->toObject();
    }
}
printf("%d x find(): %.2f µs per call\n", $n * $rounds, (hrtime(true) - $start) / 1e3 / ($n * $rounds));
