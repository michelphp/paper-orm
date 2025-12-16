<?php

namespace Michel\PaperORM\Hydrator;

use InvalidArgumentException;
use LogicException;
use Michel\PaperORM\Cache\EntityMemcachedCache;
use Michel\PaperORM\Collection\ObjectStorage;
use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Mapper\ColumnMapper;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Mapping\OneToMany;
use Michel\PaperORM\Proxy\ProxyFactory;
use Michel\PaperORM\Proxy\ProxyInterface;
use Michel\PaperORM\Schema\SchemaInterface;
use ReflectionClass;

final class EntityHydrator extends AbstractEntityHydrator
{
    private EntityMemcachedCache $cache;
    private SchemaInterface $schema;

    public function __construct(SchemaInterface $schema, EntityMemcachedCache $cache)
    {
        $this->cache = $cache;
        $this->schema = $schema;
    }

    protected function instantiate(string $class, array $data): object
    {
        $primaryKey = ColumnMapper::getPrimaryKeyColumnName($class);

        $object = $this->cache->get($class, $data[$primaryKey]) ?: ProxyFactory::create($class);

        $this->cache->set($class, $data[$primaryKey], $object);

        return $object;
    }

    protected function getSchema(): SchemaInterface
    {
        return $this->schema;
    }
}

