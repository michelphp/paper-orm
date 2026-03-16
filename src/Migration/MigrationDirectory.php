<?php

namespace Michel\PaperORM\Migration;


final class MigrationDirectory
{
    private string $dir;

    public function __construct(string $dir)
    {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new \InvalidArgumentException("Cannot create directory '$dir', check permissions.");
            }
        }

        if (!is_writable($dir)) {
            throw new \RuntimeException("Directory '$dir' is not writable.");
        }

        $this->dir = $dir;
    }

    public function getMigrations(): array
    {
        $migrations = [];
        foreach (new \DirectoryIterator($this->dir) as $file) {
            if ($file->getExtension() !== 'sql') {
                continue;
            }
            $version = pathinfo($file->getBasename(), PATHINFO_FILENAME);
            $migrations[$version] = $file->getPathname();
        }
        ksort($migrations);
        return $migrations;
    }

    public function getMigration(string $version): string
    {
        $migrations = $this->getMigrations();
        if (!array_key_exists($version, $migrations)) {
            throw new \InvalidArgumentException("Version '$version' does not exist.");
        }

        return $migrations[$version];
    }

    /**
     * @return string
     */
    public function getDir(): string
    {
        return $this->dir;
    }
}
