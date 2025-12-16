<?php

namespace Michel\PaperORM\Query;

use Michel\PaperORM\Expression\Expr;
use Michel\PaperORM\Mapper\ColumnMapper;

final class Fetcher
{
    private QueryBuilder $queryBuilder;
    private array $arguments = [];
    private bool $collection;


    public function __construct(QueryBuilder $queryBuilder, bool $collection = true)
    {
        $this->queryBuilder = $queryBuilder;
        $this->collection = $collection;
    }

    public function where(Expr ...$expressions): Fetcher
    {
        $alias = $this->queryBuilder->getPrimaryAlias();
        foreach ($expressions as $expression) {
            $this->queryBuilder->where($expression->toPrepared($alias));
            foreach ($expression->getBoundValue() as $k => $v) {
                $this->arguments[ltrim($k, ':')] = $v;
            }
        }
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): Fetcher
    {
        $alias = $this->queryBuilder->getPrimaryAlias();
        $this->queryBuilder->orderBy(sprintf('%s.%s', $alias, $column), $direction);
        return $this;
    }

    public function offset(?int $offset): Fetcher
    {
        $this->queryBuilder->setFirstResult($offset);
        return $this;
    }
    public function limit(?int $limit): Fetcher
    {
        $this->queryBuilder->setMaxResults($limit);
        return $this;
    }

    public function first(): Fetcher
    {
        $this->collection = false;
        return $this;
    }

    public function with(string...$relationsClasses): Fetcher
    {
        foreach ($relationsClasses as $relationClass) {
            $this->joinRelation('left', $relationClass);
        }

        return $this;
    }

    public function has(string...$relationsClasses): Fetcher
    {
        foreach ($relationsClasses as $relationClass) {
            $this->joinRelation('inner', $relationClass);
        }
        return $this;
    }

    public function toArray(): ?array
    {
        return $this->getResult(QueryBuilder::HYDRATE_ARRAY);
    }
    public function toLazyArray(): callable
    {
        return function () {
            return $this->toArray();
        };
    }

    public function toObject()
    {
       return $this->getResult(QueryBuilder::HYDRATE_OBJECT);
    }

    public function toLazyObject(): callable
    {
        return function () {
            return $this->toObject();
        };
    }

    public function toReadOnlyObject()
    {
        return $this->getResult(QueryBuilder::HYDRATE_OBJECT_READONLY) ;
    }

    public function toLazyReadOnlyObject(): callable
    {
        return function () {
            return $this->toReadOnlyObject();
        };
    }

    public function toCount(): int
    {
        return $this->queryBuilder->getCountResult();
    }

    private function getResult(string $hydrationMode)
    {
        $this->queryBuilder->setParams($this->arguments);
        if ($this->collection) {
            return $this->queryBuilder->getResult($hydrationMode);
        }

        return $this->queryBuilder->getOneOrNullResult($hydrationMode);

    }

    private function joinRelation(string $type, string $expression): void
    {
        $alias = $this->queryBuilder->getPrimaryAlias();
        if (class_exists($expression)) {
            if ($type === 'left') {
                $this->queryBuilder->leftJoin($alias, $expression);
                return;
            }
            $this->queryBuilder->innerJoin($alias, $expression);
            return;
        }

        $currentEntityName = $this->queryBuilder->getPrimaryEntityName();
        $currentAlias = $alias;
        foreach (explode('.', $expression) as $propertyName) {
            $column = ColumnMapper::getRelationColumnByProperty($currentEntityName, $propertyName);
            $targetEntityName = $column->getTargetEntity();
            $property = $column->getProperty();
            if ($type === 'left') {
                $this->queryBuilder->leftJoin($currentAlias, $targetEntityName, $property);
            } else {
                $this->queryBuilder->innerJoin($currentAlias, $targetEntityName, $property);
            }
            $currentAliases = $this->queryBuilder->getAliasesFromEntityName($targetEntityName, $property);
            $currentAlias = end($currentAliases);
            $currentEntityName = $targetEntityName;
        }
    }
}
