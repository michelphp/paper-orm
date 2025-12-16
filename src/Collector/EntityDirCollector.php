<?php

namespace Michel\PaperORM\Collector;

final class EntityDirCollector
{
    
    /** @var string[] */
    private array $dirs = [];

    public static function bootstrap(array $dirs = []): EntityDirCollector
    {
        // Core entity directories of the ORM (always loaded first)
        $coreDirs = [
            dirname(__DIR__) . '/Internal/Entity',
        ];
        $dirs = array_merge($coreDirs, $dirs);
        return new EntityDirCollector($dirs);
    }

    /**
     * @param string|string[] $dirs
     */
    private function __construct(array $dirs = [])
    {
        foreach ($dirs as $index => $dir) {
            if (!is_string($dir)) {
                $given = gettype($dir);
                throw new \InvalidArgumentException(sprintf(
                    'EntityDirCollector::__construct(): each directory must be a string, %s given at index %d.',
                    $given,
                    $index
                ));
            }

            if (empty($dir)) {
                throw new \InvalidArgumentException(sprintf(
                    'EntityDirCollector::__construct(): directory at index %d is an empty string.',
                    $index
                ));
            }

            $this->add($dir);
        }
    }

    public function add(string $dir): void
    {
        if (!is_dir($dir)) {
            throw new \InvalidArgumentException(sprintf(
                'EntityDirCollector::add(): directory "%s" does not exist.',
                $dir
            ));
        }
        $dir = rtrim($dir, '/');
        if (!in_array($dir, $this->dirs, true)) {
            $this->dirs[] = $dir;
        }
    }

    /**
     * @return string[]
     */
    public function all(): array
    {
        return $this->dirs;
    }

    public function count(): int
    {
        return count($this->dirs);
    }
}
