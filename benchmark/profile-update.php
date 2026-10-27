<?php

declare(strict_types=1);

/**
 * Runs only PaperORM updates (load N managed users, modify them, one flush()), to profile the write path
 * function by function with SPX:
 *
 *   SPX_ENABLED=1 SPX_REPORT=flat SPX_FP_LIMIT=40 php -d extension=spx.so profile-update.php [--n=1000] [--rounds=20]
 *
 * Loading the users is part of the profile too: look at the persistence functions (EntityPersistence, proxy, serializer).
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

$elapsed = 0;
for ($round = 0; $round < $rounds; $round++) {
    $em->clear();
    $users = $em->getRepository(User::class)->findBy()->toObject();

    $start = hrtime(true);
    foreach ($users as $user) {
        $user->setAge($user->getAge() + 1);
        $em->persist($user);
    }
    $em->flush();
    $elapsed += hrtime(true) - $start;
}
printf("%d x update: %.2f µs per entity (modify + flush only)\n", $n * $rounds, $elapsed / 1e3 / ($n * $rounds));
