<?php

namespace Michel\PaperORM;

use Michel\EventDispatcher\EventDispatcher;
use Michel\PaperORM\Cache\EntityMemcachedCache;
use Michel\PaperORM\Manager\PaperKeyValueManager;
use Michel\PaperORM\Manager\PaperSequenceManager;
use Michel\PaperORM\Mapper\EntityMapper;
use Michel\PaperORM\Platform\PlatformInterface;
use Michel\PaperORM\Repository\Repository;
use Psr\EventDispatcher\EventDispatcherInterface;

class EntityManager implements EntityManagerInterface
{
    private PaperConnection $connection;

    private UnitOfWork $unitOfWork;

    /**
     * @var array<string, Repository>
     */
    private array $repositories = [];

    private EntityMemcachedCache $cache;

    private EventDispatcherInterface $dispatcher;
    private ?PlatformInterface $platform = null;
    private PaperKeyValueManager $keyValueManager;
    private PaperSequenceManager $sequenceManager;

    public static function createFromConfig(PaperConfiguration $config): self
    {
        return new self($config);
    }
    private function __construct(PaperConfiguration $config)
    {
        $this->connection = $config->getConnection();
        $this->unitOfWork = $config->getUnitOfWork();
        $this->cache = $config->getCache();
        $this->dispatcher = new EventDispatcher($config->getListeners());
        $this->keyValueManager = new PaperKeyValueManager($this);
        $this->sequenceManager = new PaperSequenceManager($this->keyValueManager);
    }

    public function persist(object $entity): void
    {
        $this->unitOfWork->persist($entity);
    }

    public function remove(object $entity): void
    {
        $this->unitOfWork->remove($entity);
    }

    public function flush(object $entity = null ): void
    {
        foreach ($this->unitOfWork->getEntityInsertions() as &$entityToInsert) {
            if ($entity && $entity !== $entityToInsert) {
                continue;
            }
            $repository = $this->getRepository(get_class($entityToInsert));
            $repository->insert($entityToInsert);
            $this->unitOfWork->unsetEntity($entityToInsert);
        }

        foreach ($this->unitOfWork->getEntityUpdates() as $entityToUpdate) {
            if ($entity && $entity !== $entityToUpdate) {
                continue;
            }
            $repository = $this->getRepository(get_class($entityToUpdate));
            $repository->update($entityToUpdate);
            $this->unitOfWork->unsetEntity($entityToUpdate);
        }

        foreach ($this->unitOfWork->getEntityDeletions() as $entityToDelete) {
            if ($entity && $entity !== $entityToDelete) {
                continue;
            }
            $repository = $this->getRepository(get_class($entityToDelete));
            $repository->delete($entityToDelete);
            $this->unitOfWork->unsetEntity($entityToDelete);
        }

        if ($entity) {
            $this->unitOfWork->unsetEntity($entity);
            return;
        }
        $this->unitOfWork->clear();
    }

    public function registry(): PaperKeyValueManager
    {
        return $this->keyValueManager;
    }

    public function sequence(): PaperSequenceManager
    {
        return $this->sequenceManager;
    }

    public function getRepository(string $entity): Repository
    {
        $repositoryName = EntityMapper::getRepositoryName($entity);
        if ($repositoryName === null) {
            $repositoryName = 'ProxyRepository' . $entity;
        }

        $dispatcher = $this->dispatcher;
        if (!isset($this->repositories[$repositoryName])) {
            if (!class_exists($repositoryName)) {
                $repository = new class($entity, $this, $dispatcher) extends Repository {
                    private string $entityName;

                    public function __construct($entityName, EntityManager $em, EventDispatcherInterface $dispatcher = null)
                    {
                        $this->entityName = $entityName;
                        parent::__construct($em, $dispatcher);
                    }

                    public function getEntityName(): string
                    {
                        return $this->entityName;
                    }
                };
            } else {
                $repository = new $repositoryName($this, $dispatcher);
            }
            $this->repositories[$repositoryName] = $repository;
        }

        return $this->repositories[$repositoryName];
    }


    public function getPlatform(): PlatformInterface
    {
        if ($this->platform === null) {
            $driver = $this->connection->getDriver();
            $this->platform = $driver->createDatabasePlatform($this->getConnection());
        }
        return $this->platform;
    }


    public function getConnection(): PaperConnection
    {
        return $this->connection;
    }

    public function getCache(): EntityMemcachedCache
    {
        return $this->cache;
    }

    public function clear(): void
    {
        $this->getCache()->clear();
    }

}
