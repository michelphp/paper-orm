<?php

declare(strict_types=1);

namespace Bench\Adapter;

use Composer\InstalledVersions;

final class Adapters
{
    public const ALL = ['paper', 'doctrine'];

    public static function create(string $name): OrmAdapter
    {
        switch ($name) {
            case 'paper':
                return new PaperAdapter();
            case 'doctrine':
                return new DoctrineAdapter();
        }

        throw new \InvalidArgumentException(sprintf('Unknown ORM "%s", expected one of: %s', $name, implode(', ', self::ALL)));
    }

    public static function packageVersion(string $package): string
    {
        if (!InstalledVersions::isInstalled($package)) {
            return 'not installed';
        }

        $version = (string) InstalledVersions::getPrettyVersion($package);
        $reference = InstalledVersions::getReference($package);
        if ($reference !== null && strpos($version, 'dev-') === 0) {
            $version .= '@' . substr($reference, 0, 7);
        }

        // A path repository is symlinked: the reference recorded at install time is stale, ask git.
        $path = InstalledVersions::getInstallPath($package);
        if ($path !== null && is_dir($path . '/.git')) {
            $branch = trim((string) shell_exec(sprintf('git -C %s rev-parse --abbrev-ref HEAD 2>/dev/null', escapeshellarg($path))));
            $commit = trim((string) shell_exec(sprintf('git -C %s rev-parse --short HEAD 2>/dev/null', escapeshellarg($path))));
            $dirty = trim((string) shell_exec(sprintf('git -C %s status --porcelain --untracked-files=no 2>/dev/null', escapeshellarg($path))));
            if ($branch !== '' && $commit !== '') {
                $version = sprintf('%s@%s%s (local)', $branch, $commit, $dirty !== '' ? '+changes' : '');
            }
        }

        return $version;
    }
}
