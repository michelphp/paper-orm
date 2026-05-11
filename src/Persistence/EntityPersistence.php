<?php

namespace Michel\PaperORM\Persistence;

use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\EntityManagerInterface;
use Michel\PaperORM\Event\Create\PostCreateEvent;
use Michel\PaperORM\Event\Create\PreCreateEvent;
use Michel\PaperORM\Event\Delete\PostDeleteEvent;
use Michel\PaperORM\Event\Delete\PreDeleteEvent;
use Michel\PaperORM\Event\Update\PostUpdateEvent;
use Michel\PaperORM\Event\Update\PreUpdateEvent;
use Michel\PaperORM\Hydrator\ReadOnlyEntityHydrator;
use Michel\PaperORM\Mapper\ColumnMapper;
use Michel\PaperORM\Mapper\EntityMapper;
use Michel\PaperORM\Platform\PlatformInterface;
use Michel\PaperORM\Proxy\ProxyInterface;
use Michel\PaperORM\Serializer\SerializerToDb;
use Michel\SqlMapper\QueryBuilder;
use Psr\EventDispatcher\EventDispatcherInterface;

class EntityPersistence
{
    private EntityManagerInterface $em;

    private PlatformInterface $platform;
    private \SplObjectStorage $managed;

    private ?EventDispatcherInterface $dispatcher;
    public function __construct(EntityManagerInterface $em, EventDispatcherInterface $dispatcher = null)
    {
        $this->em = $em;
        $this->platform = $em->getPlatform();
        $this->dispatcher = $dispatcher;
        $this->managed = new \SplObjectStorage();
    }

    public function insert(object $entity): int
    {
        /**
         * @var EntityInterface $entity
         */
        $this->checkEntity($entity);
        if ($entity->getPrimaryKeyValue() !== null || $this->managed->contains($entity)) {
            throw new \LogicException(sprintf(
                '%s cannot insert entity of type "%s": entity already managed (has primary key, is a proxy, or is attached).',
                static::class,
                get_class($entity)
            ));
        }

        if ($this->dispatcher) {
            $this->dispatcher->dispatch(new PreCreateEvent($this->em, $entity));
        }

        $schema = $this->platform->getSchema();
        $tableName = EntityMapper::getTable($entity);
        $qb = QueryBuilder::insert($schema->quote($tableName));

        $values = [];
        foreach ((new SerializerToDb($entity, $schema))->serialize() as $key => $value) {
            $qb->setValue($schema->quote($key), ":$key");
            $values[$key] = $value;
        }
        $rows = $this->execute($qb, $values);
        $conn = $this->platform->getConnection();
        $lastInsertId = $conn->getPdo()->lastInsertId();
        if ($rows > 0) {
            $primaryKeyColumn = ColumnMapper::getPrimaryKeyColumnName($entity);
            (new ReadOnlyEntityHydrator($schema))->hydrate($entity, [$primaryKeyColumn => $lastInsertId]);
            $this->managed->attach($entity);
            if ($this->dispatcher) {
                $this->dispatcher->dispatch(new PostCreateEvent($this->em, $entity));
            }
        }
        return $rows;
    }

    public function update(object $entity): int
    {
        /**
         * @var ProxyInterface|EntityInterface $entity
         */
        $this->checkEntity($entity, true);
        if ($entity->getPrimaryKeyValue() === null) {
            throw new \LogicException(static::class . sprintf(' Cannot update an entity %s without a primary key ', get_class($entity)));
        }

        if ($entity instanceof ProxyInterface) {
            if (!$entity->__wasModified()) {
                return 0;
            }
        }

        if ($this->dispatcher) {
            $this->dispatcher->dispatch(new PreUpdateEvent($this->em, $entity));
        }

        if ($entity instanceof ProxyInterface) {
            $propertiesModified = $entity->__getPropertiesModified();
        } else {
            $propertiesModified = [];
        }

        $tableName = EntityMapper::getTable($entity);
        $schema = $this->platform->getSchema();
        $qb = QueryBuilder::update($schema->quote($tableName))
            ->where(
                sprintf('%s = %s',
                    $schema->quote(ColumnMapper::getPrimaryKeyColumnName($entity)),
                    $entity->getPrimaryKeyValue()
                )
            );

        $values = [];
        foreach ((new SerializerToDb($entity, $schema))->serialize($propertiesModified) as $key => $value) {
            $qb->set($schema->quote($key), ":$key");
            $values[$key] = $value;
        }
        $rows = $this->execute($qb, $values);
        if ($rows > 0) {
            if ($entity instanceof ProxyInterface) {
                $entity->__reset();
            }
            if ($this->dispatcher) {
                $this->dispatcher->dispatch(new PostUpdateEvent($this->em, $entity));
            }
        }
        return $rows;
    }

    public function delete(object $entity): int
    {
        /**
         * @var ProxyInterface|EntityInterface $entity
         */
        $this->checkEntity($entity, true);
        if ($entity->getPrimaryKeyValue() === null) {
            throw new \LogicException(static::class . sprintf(' Cannot delete an entity %s without a primary key ', get_class($entity)));
        }

        if ($this->dispatcher) {
            $this->dispatcher->dispatch(new PreDeleteEvent($this->em, $entity));
        }

        $tableName = EntityMapper::getTable($entity);
        $schema = $this->platform->getSchema();
        $qb = QueryBuilder::delete($schema->quote($tableName))
            ->where(sprintf('%s = :id', $schema->quote(ColumnMapper::getPrimaryKeyColumnName($entity))));

        $rows = $this->execute($qb, [
            'id' => $entity->getPrimaryKeyValue(),
        ]);
        if ($rows > 0) {
            if ($entity instanceof ProxyInterface) {
                $entity->__destroy();
            }
            if ($this->managed->contains($entity)) {
                $this->managed->detach($entity);
            }

            if ($this->dispatcher) {
                $this->dispatcher->dispatch(new PostDeleteEvent($this->em, $entity));
            }
        }
        return $rows;
    }

    public function clear(): void
    {
        $this->managed->removeAll($this->managed);
    }

    private function execute(string $query, array $params = []): int
    {
        $conn = $this->platform->getConnection();
        return $conn->executeStatement($query, $params);
    }

    private function checkEntity(object $entity,  bool $requireManaged = false): void
    {
        if (!$entity instanceof EntityInterface) {
            throw new \LogicException(sprintf(
                'Invalid entity of type "%s". Expected an instance of "%s".',
                get_class($entity),
                EntityInterface::class
            ));
        }

        if ($requireManaged) {
            $isManaged = $this->managed->contains($entity);
            $isProxy = $entity instanceof ProxyInterface && $entity->__isInitialized();
            if (!$isManaged && !$isProxy) {
                throw new \LogicException(sprintf(
                    'Entity of type "%s" is not managed by ORM',
                    get_class($entity)
                ));
            }
        }
    }

}
