<?php

namespace Michel\PaperORM\Repository;

use InvalidArgumentException;
use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\EntityManagerInterface;
use Michel\PaperORM\Expression\Expr;
use Michel\PaperORM\Mapper\ColumnMapper;
use Michel\PaperORM\Mapper\EntityMapper;
use Michel\PaperORM\Persistence\EntityPersistence;
use Michel\PaperORM\Query\Fetcher;
use Michel\PaperORM\Query\QueryBuilder;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class Repository
{
    private EntityManagerInterface $em;
    private EntityPersistence $ep;

    public function __construct(EntityManagerInterface $em, EventDispatcherInterface $dispatcher = null)
    {
        $this->em = $em;
        $this->ep = new EntityPersistence($em, $dispatcher);
    }

    /**
     * Get the name of the table associated with this repository.
     *
     * @return string The name of the table.
     */
    final public function getTableName(): string
    {
        $entityName = $this->getEntityName();
        return EntityMapper::getTable($entityName);
    }

    /**
     * Get the name of the model associated with this repository.
     *
     * @return class-string<EntityInterface> The name of the model.
     */
    abstract public function getEntityName(): string;


    public function findBy(array $arguments = []): Fetcher
    {
        $expressions = [];
        foreach ($arguments as $key => $value) {
            if ($value instanceof EntityInterface) {
                $value = $value->getPrimaryKeyValue();
            } elseif (is_array($value)) {
                $expressions[] = Expr::in($key, $value);
                continue;
            } elseif (is_null($value)) {
                $expressions[] = Expr::isNull($key);
                continue;
            } elseif (is_string($value) && strtoupper($value) === "!NULL") {
                $expressions[] = Expr::isNotNull($key);
                continue;
            } elseif (!is_scalar($value)) {
                throw new InvalidArgumentException(
                    sprintf('Argument "%s" must be scalar, array, null or EntityInterface, %s given', $key, gettype($value))
                );
            }
            $expressions[] = Expr::equal($key, $value);
        }
        return (new Fetcher($this->qb(), true))->where(...$expressions);
    }

    public function findOneBy(array $arguments = []): Fetcher
    {
        return $this->findBy($arguments)->first();
    }

    public function find(int $pk): Fetcher
    {
        $entityName = $this->getEntityName();
        $primaryKeyColumn = ColumnMapper::getPrimaryKeyColumnName($entityName);
        return $this->findOneBy([$primaryKeyColumn => $pk]);
    }

    public function insert(object $entityToInsert): int
    {
        return $this->ep->insert($entityToInsert);
    }

    public function update(object $entityToUpdate): int
    {
        return $this->ep->update($entityToUpdate);
    }

    public function delete(object $entityToDelete): int
    {
        $rows = $this->ep->delete($entityToDelete);
        $this->em->getCache()->invalidate(get_class($entityToDelete), $entityToDelete->getPrimaryKeyValue());
        return $rows;
    }

    public function qb(...$propertiesToSelect): QueryBuilder
    {
        $queryBuilder = new QueryBuilder($this->em);
        return $queryBuilder->select($this->getEntityName(), $propertiesToSelect);
    }

    public function clear(): void
    {
        $this->ep->clear();
        $this->em->getCache()->clear();
    }
}
