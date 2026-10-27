<?php

declare(strict_types=1);

/**
 * PaperORM vs Doctrine ORM — runtime benchmark.
 *
 *   php bench.php [--n=1000] [--posts=5] [--iterations=5] [--scenario=find_all,join] [--orm=paper,doctrine] [--no-save]
 *
 * Every measurement runs in a fresh PHP process with its own in-memory SQLite database.
 * The first run of each (scenario, ORM) pair is a discarded warm-up, the median of the others is reported.
 */

require __DIR__ . '/vendor/autoload.php';

use Bench\Adapter\Adapters;
use Bench\Worker;

$options = getopt('', ['n:', 'posts:', 'iterations:', 'scenario:', 'orm:', 'no-save']);
$n = max(1, (int) ($options['n'] ?? 1000));
$posts = max(0, (int) ($options['posts'] ?? 5));
$iterations = max(1, (int) ($options['iterations'] ?? 5));
$scenarios = isset($options['scenario']) ? explode(',', (string) $options['scenario']) : array_keys(Worker::SCENARIOS);
$orms = isset($options['orm']) ? explode(',', (string) $options['orm']) : Adapters::ALL;

foreach ($scenarios as $scenario) {
    if (!isset(Worker::SCENARIOS[$scenario])) {
        fwrite(STDERR, sprintf("Unknown scenario \"%s\". Available: %s\n", $scenario, implode(', ', array_keys(Worker::SCENARIOS))));
        exit(1);
    }
}
foreach ($orms as $orm) {
    if (!in_array($orm, Adapters::ALL, true)) {
        fwrite(STDERR, sprintf("Unknown ORM \"%s\". Available: %s\n", $orm, implode(', ', Adapters::ALL)));
        exit(1);
    }
}

$environment = [
    'date' => date('c'),
    'php' => PHP_VERSION,
    'os' => PHP_OS_FAMILY,
    'opcache_cli' => filter_var(ini_get('opcache.enable_cli'), FILTER_VALIDATE_BOOLEAN) && extension_loaded('Zend OPcache'),
    'xdebug' => extension_loaded('xdebug'),
    'n' => $n,
    'posts_per_user' => $posts,
    'iterations' => $iterations,
    'versions' => [],
];
foreach ($orms as $orm) {
    $adapter = Adapters::create($orm);
    $environment['versions'][$adapter->label()] = $adapter->version();
}

echo "PaperORM vs Doctrine — runtime benchmark\n";
echo str_repeat('=', 41) . "\n";
printf("PHP %s (%s) | OPcache CLI: %s | Xdebug: %s\n", PHP_VERSION, PHP_OS_FAMILY, $environment['opcache_cli'] ? 'on' : 'off', $environment['xdebug'] ? 'ON (results are not meaningful)' : 'off');
foreach ($environment['versions'] as $label => $version) {
    printf("%-9s %s\n", $label, $version);
}
printf("N = %d users, %d posts per user (join), %d iterations + 1 warm-up, median reported\n\n", $n, $posts, $iterations);

$results = [];
foreach ($scenarios as $scenario) {
    printf("> %-15s %s\n", $scenario, Worker::SCENARIOS[$scenario]);
    foreach ($orms as $orm) {
        $runs = [];
        $error = null;
        for ($i = 0; $i <= $iterations; $i++) {
            [$run, $error] = runWorker($orm, $scenario, $n, $posts);
            if ($error !== null) {
                break;
            }
            if ($i > 0) {
                $runs[] = $run;
            }
        }
        $results[$scenario][$orm] = summarize($runs, $error);
    }
}

echo "\n";
printReport($results, $scenarios, $orms);

if (!isset($options['no-save'])) {
    $dir = __DIR__ . '/results';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $json = json_encode(['environment' => $environment, 'results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $file = $dir . '/bench-' . date('Ymd-His') . '.json';
    file_put_contents($file, $json);
    file_put_contents($dir . '/bench-latest.json', $json);
    printf("\nResults saved to %s\n", $file);
}

/**
 * @return array{0: ?array, 1: ?string}
 */
function runWorker(string $orm, string $scenario, int $n, int $posts): array
{
    $command = [
        PHP_BINARY,
        '-d', 'memory_limit=-1',
        __DIR__ . '/run.php',
        '--orm=' . $orm,
        '--scenario=' . $scenario,
        '--n=' . $n,
        '--posts=' . $posts,
    ];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        return [null, 'Unable to start the worker process'];
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    $data = json_decode((string) $stdout, true);
    if ($exitCode !== 0 || !is_array($data)) {
        return [null, trim($stderr . ' ' . $stdout) ?: 'Worker exited with code ' . $exitCode];
    }

    return [$data, null];
}

function summarize(array $runs, ?string $error): array
{
    if ($error !== null || $runs === []) {
        return ['error' => $error ?? 'No run'];
    }

    $times = array_column($runs, 'time_ms');
    $memories = array_column($runs, 'memory');
    $failed = array_filter($runs, static function (array $run) {
        return $run['ok'] !== true;
    });
    $first = reset($failed) ?: $runs[0];

    return [
        'time_ms' => median($times),
        'time_min_ms' => min($times),
        'time_max_ms' => max($times),
        'memory' => (int) median($memories),
        'ok' => $failed === [],
        'check' => $first['check'],
        'expected' => $first['expected'],
    ];
}

function median(array $values): float
{
    sort($values);
    $count = count($values);
    $middle = intdiv($count, 2);

    return $count % 2 ? (float) $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;
}

function printReport(array $results, array $scenarios, array $orms): void
{
    $labels = [];
    foreach ($orms as $orm) {
        $labels[$orm] = Adapters::create($orm)->label();
    }

    $header = sprintf('%-15s', 'Scenario');
    foreach ($orms as $orm) {
        $header .= sprintf(' | %-24s', $labels[$orm] . ' (time / memory)');
    }
    if (count($orms) === 2) {
        $header .= ' | Time ratio';
    }
    echo $header . "\n" . str_repeat('-', strlen($header)) . "\n";

    $problems = [];
    foreach ($scenarios as $scenario) {
        $line = sprintf('%-15s', $scenario);
        foreach ($orms as $orm) {
            $result = $results[$scenario][$orm];
            if (isset($result['error'])) {
                $line .= sprintf(' | %-24s', 'ERROR');
                $problems[] = sprintf('%s / %s: %s', $scenario, $labels[$orm], $result['error']);
                continue;
            }
            $cell = sprintf('%s / %s', formatTime($result['time_ms']), formatBytes($result['memory']));
            if (!$result['ok']) {
                $cell .= ' (!)';
                $problems[] = sprintf('%s / %s: wrong result, got %s, expected %s', $scenario, $labels[$orm], json_encode($result['check']), json_encode($result['expected']));
            }
            $line .= sprintf(' | %-24s', $cell);
        }
        if (count($orms) === 2) {
            $line .= ' | ' . formatRatio($results[$scenario], $orms, $labels);
        }
        echo $line . "\n";
    }

    if ($problems !== []) {
        echo "\nProblems:\n";
        foreach ($problems as $problem) {
            echo '  - ' . $problem . "\n";
        }
    }
}

function formatRatio(array $scenarioResults, array $orms, array $labels): string
{
    [$a, $b] = $orms;
    if (isset($scenarioResults[$a]['error']) || isset($scenarioResults[$b]['error'])) {
        return '-';
    }
    $timeA = max($scenarioResults[$a]['time_ms'], 0.001);
    $timeB = max($scenarioResults[$b]['time_ms'], 0.001);
    if ($timeA <= $timeB) {
        return sprintf('%s x%.2f faster', $labels[$a], $timeB / $timeA);
    }
    return sprintf('%s x%.2f faster', $labels[$b], $timeA / $timeB);
}

function formatTime(float $ms): string
{
    return $ms >= 1000 ? sprintf('%.2f s', $ms / 1000) : sprintf('%.2f ms', $ms);
}

function formatBytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return sprintf('%.1f MB', $bytes / 1048576);
    }
    return sprintf('%.0f KB', $bytes / 1024);
}
