<?php

declare(strict_types=1);

/**
 * PaperORM vs Doctrine ORM — installed footprint benchmark.
 *
 *   php size.php [--composer=/path/to/composer(.phar)]
 *
 * Each ORM is installed ALONE in an empty Composer project (--no-dev, dist archives),
 * exactly like `composer require <orm>` would do in a user project, then the whole
 * vendor/ directory is measured: the ORM and every dependency it pulls.
 *
 * Does not need `composer install` in this directory, only Composer and network access.
 */

$options = getopt('', ['composer:']);
$composer = (string) ($options['composer'] ?? (getenv('COMPOSER_BIN') ?: 'composer'));

$targets = [
    'PaperORM' => [
        'require' => ['michel/paper-orm' => '*'],
        'minimum-stability' => 'alpha',
        'prefer-stable' => true,
    ],
    'Doctrine' => [
        'require' => ['doctrine/orm' => '*'],
    ],
];

$workDir = __DIR__ . '/var/size';
$results = [];

echo "PaperORM vs Doctrine — installed footprint (composer require, --no-dev, dist)\n";
echo str_repeat('=', 76) . "\n";
printf("PHP %s — dependencies are resolved for this PHP version\n\n", PHP_VERSION);

foreach ($targets as $label => $manifest) {
    $dir = $workDir . '/' . strtolower($label);
    removeDirectory($dir);
    mkdir($dir, 0777, true);

    $manifest['name'] = 'bench/size-' . strtolower($label);
    $manifest['config'] = ['allow-plugins' => false];
    file_put_contents($dir . '/composer.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    echo "Installing {$label}... ";
    $error = composerInstall($composer, $dir);
    if ($error !== null) {
        echo "FAILED\n{$error}\n";
        exit(1);
    }
    echo "done\n";

    $results[$label] = measureVendor($dir . '/vendor');
}

echo "\n";
printSummary($results);
foreach ($results as $label => $result) {
    printPackages($label, $result['packages']);
}

$resultDir = __DIR__ . '/results';
if (!is_dir($resultDir)) {
    mkdir($resultDir, 0777, true);
}
$json = json_encode(['date' => date('c'), 'php' => PHP_VERSION, 'results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents($resultDir . '/size-' . date('Ymd-His') . '.json', $json);
file_put_contents($resultDir . '/size-latest.json', $json);
printf("\nResults saved to %s\n", $resultDir . '/size-latest.json');

function composerInstall(string $composer, string $dir): ?string
{
    $command = substr($composer, -5) === '.phar' ? [PHP_BINARY, $composer] : [$composer];
    $command = array_merge($command, [
        'update',
        '--no-dev',
        '--prefer-dist',
        '--no-interaction',
        '--no-progress',
        '--no-plugins',
        '--no-scripts',
        '--working-dir=' . $dir,
    ]);

    $log = $dir . '/composer.log';
    $process = proc_open($command, [1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes);
    if (!is_resource($process)) {
        return 'Unable to run Composer: ' . implode(' ', $command);
    }
    if (proc_close($process) !== 0) {
        return trim((string) file_get_contents($log));
    }

    return null;
}

function measureVendor(string $vendorDir): array
{
    $packages = [];
    foreach (readInstalledPackages($vendorDir) as $package) {
        $path = realpath($vendorDir . '/composer/' . $package['install-path']);
        $packages[$package['name']] = ['version' => $package['version']] + measureDirectory((string) $path);
    }
    uasort($packages, static function (array $a, array $b) {
        return $b['bytes'] <=> $a['bytes'];
    });

    return [
        'total' => measureDirectory($vendorDir),
        'packages' => $packages,
    ];
}

function readInstalledPackages(string $vendorDir): array
{
    $installed = json_decode((string) file_get_contents($vendorDir . '/composer/installed.json'), true);

    // Composer 2 wraps the list in "packages", Composer 1 does not.
    return $installed['packages'] ?? $installed;
}

/**
 * @return array{bytes: int, files: int, php_files: int, php_lines: int}
 */
function measureDirectory(string $dir): array
{
    $stats = ['bytes' => 0, 'files' => 0, 'php_files' => 0, 'php_lines' => 0];
    if ($dir === '' || !is_dir($dir)) {
        return $stats;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $stats['bytes'] += $file->getSize();
        $stats['files']++;
        if (strtolower($file->getExtension()) === 'php') {
            $stats['php_files']++;
            $stats['php_lines'] += substr_count((string) file_get_contents($file->getPathname()), "\n");
        }
    }

    return $stats;
}

function printSummary(array $results): void
{
    $labels = array_keys($results);
    printf("%-14s", 'Total');
    foreach ($labels as $label) {
        printf(' | %14s', $label);
    }
    $ratioHeader = count($labels) === 2 ? sprintf(' | %s / %s', $labels[1], $labels[0]) : '';
    echo $ratioHeader . "\n";
    echo str_repeat('-', 14 + 17 * count($labels) + strlen($ratioHeader)) . "\n";

    $rows = [
        'Packages' => static function (array $r) {
            return count($r['packages']);
        },
        'Size' => static function (array $r) {
            return $r['total']['bytes'];
        },
        'Files' => static function (array $r) {
            return $r['total']['files'];
        },
        'PHP files' => static function (array $r) {
            return $r['total']['php_files'];
        },
        'PHP lines' => static function (array $r) {
            return $r['total']['php_lines'];
        },
    ];
    foreach ($rows as $name => $value) {
        printf('%-14s', $name);
        $values = [];
        foreach ($labels as $label) {
            $values[] = $value($results[$label]);
            printf(' | %14s', $name === 'Size' ? formatBytes(end($values)) : number_format(end($values), 0, '.', ' '));
        }
        if (count($values) === 2 && $values[0] > 0) {
            printf(' | x%.1f', $values[1] / $values[0]);
        }
        echo "\n";
    }
}

function printPackages(string $label, array $packages): void
{
    printf("\n%s — %d packages\n", $label, count($packages));
    foreach ($packages as $name => $stats) {
        printf("  %-40s %-16s %10s %6d files\n", $name, $stats['version'], formatBytes($stats['bytes']), $stats['files']);
    }
}

function formatBytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return sprintf('%.2f MB', $bytes / 1048576);
    }
    return sprintf('%.1f KB', $bytes / 1024);
}

function removeDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}
