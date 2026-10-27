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

        return $version;
    }
}
