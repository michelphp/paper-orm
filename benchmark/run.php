<?php

declare(strict_types=1);

/**
 * Internal worker: runs ONE scenario for ONE ORM and prints the result as JSON.
 * Use bench.php instead, it spawns this script in a fresh process for every measurement.
 */

require __DIR__ . '/vendor/autoload.php';

use Bench\Worker;

$options = getopt('', ['orm:', 'scenario:', 'n:', 'posts:']);

try {
    $result = Worker::run(
        (string) ($options['orm'] ?? ''),
        (string) ($options['scenario'] ?? ''),
        (int) ($options['n'] ?? 1000),
        (int) ($options['posts'] ?? 5)
    );
    echo json_encode($result);
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, sprintf('%s: %s in %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
    exit(1);
}
