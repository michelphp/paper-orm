<?php

namespace Michel\PaperORM\Mapper;

use InvalidArgumentException;
use LogicException;
use Michel\PaperORM\Cache\ColumnCache;
use Michel\PaperORM\Cache\OneToManyCache;
use Michel\PaperORM\Cache\PrimaryKeyColumnCache;
use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Entity\TableMetadataInterface;
use Michel\PaperORM\Mapping\Column\Column;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Michel\PaperORM\Mapping\OneToMany;
use ReflectionClass;

final class ColumnMapper
{

    static public function getPrimaryKeyColumn($class): PrimaryKeyColumn
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        $cache = PrimaryKeyColumnCache::getInstance();
        if (empty($cache->get($class))) {

            $columnsFiltered = array_filter(self::getColumns($class), function (Column $column) {
                return $column instanceof PrimaryKeyColumn;
            });

            if (count($columnsFiltered) === 0) {
                throw new LogicException(self::class . ' At least one primary key is required. : ' . $class);
            }

            if (count($columnsFiltered) > 1) {
                throw new LogicException(self::class . ' Only one primary key is allowed. : ' . $class);
            }

            $primaryKey = $columnsFiltered[0];
            $cache->set($class, $primaryKey);
        }
        return $cache->get($class);
    }
    static public function getPrimaryKeyColumnName($class): string
    {
        return self::getPrimaryKeyColumn($class)->getName();
    }

    /**
     * @param string|object $class
     * @return array<Column>
     */
    static public function getColumns($class): array
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        $cache = ColumnCache::getInstance();
        if (empty($cache->get($class))) {
            self::loadCache($class);
        }
        return $cache->get($class);
    }


    /**
     * @param $class
     * @return array<OneToMany>
     */
    final static public function getOneToManyRelations($class): array
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $cache = OneToManyCache::getInstance();
        if (empty($cache->get($class))) {
            $columnsMapping = [];
            if (is_subclass_of($class, EntityInterface::class)) {
                $columnsMapping = self::getColumnsMapping($class);
                $columnsMapping = array_filter($columnsMapping, function ($column) {
                    return $column instanceof OneToMany;
                });
            }

            $cache->set($class, $columnsMapping);
            self::loadCache($class);
        }

        return $cache->get($class);
    }

    static public function getColumnByProperty(string $class, string $property): ?Column
    {
        $columns = self::getColumns($class);
        foreach ($columns as $column) {
            if ($column->getProperty() === $property) {
                return $column;
            }
        }
        return null;
    }

    /**
     * @param string $class
     * @param string $property
     * @return JoinColumn|OneToMany
     */
    static public function getRelationColumnByProperty(string $class, string $property)
    {
        $columns = array_merge(self::getColumns($class) , self::getOneToManyRelations($class));
        foreach ($columns as $column) {
            if ($column->getProperty() === $property) {
                if ($column instanceof JoinColumn) {
                    return $column;
                }
                if ($column instanceof OneToMany) {
                    return $column;
                }
            }
        }
        throw new \InvalidArgumentException(sprintf('Property %s not found in class %s or is a collection and cannot be used in an expression', $property, $class));
    }


    static private function loadCache(string $class): void
    {
        if (is_subclass_of($class, EntityInterface::class)) {
            $columnsMapping = self::getColumnsMapping($class);
            $columnsMapping = array_filter($columnsMapping, function ($column) {
                return $column instanceof Column;
            });
        }
        if (empty($columnsMapping)) {
            throw new InvalidArgumentException('No columns found. : ' . $class);
        }

        ColumnCache::getInstance()->set($class, $columnsMapping);
    }

    static private function getColumnsMapping($class): array
    {
        if (is_subclass_of($class, TableMetadataInterface::class)) {
            return $class::columnsMapping();
        }

        if (PHP_VERSION_ID >= 80000) {
            $columns   = [];
            $refClass  = new \ReflectionClass($class);
            while ($refClass) {
                foreach ($refClass->getProperties() as $property) {
                    if ($property->getDeclaringClass()->getName() !== $refClass->getName()) {
                        continue;
                    }

                    foreach ($property->getAttributes(
                        Column::class,
                        \ReflectionAttribute::IS_INSTANCEOF
                    ) as $attr) {
                        $instance = $attr->newInstance();
                        if (method_exists($instance, 'bindProperty')) {
                            $instance->bindProperty($property->getName());
                        }
                        $columns[] = $instance;
                    }

                    foreach ($property->getAttributes(
                        OneToMany::class,
                        \ReflectionAttribute::IS_INSTANCEOF
                    ) as $attr) {
                        $instance = $attr->newInstance();
                        if (method_exists($instance, 'bindProperty')) {
                            $instance->bindProperty($property->getName());
                        }
                        $columns[] = $instance;
                    }
                }

                $refClass = $refClass->getParentClass();
            }

            return $columns;
        }

        if (method_exists($class, 'columnsMapping')) {
            return $class::columnsMapping();
        }

        throw new \LogicException(sprintf(
            'Entity %s must define columns via interface, attribute or static method : ::columnsMapping() or implement %s',
            is_object($class) ? get_class($class) : $class, TableMetadataInterface::class
        ));

    }
}
