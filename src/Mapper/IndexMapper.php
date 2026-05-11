<?php

namespace Michel\PaperORM\Mapper;


use Michel\PaperORM\Cache\ColumnCache;
use Michel\PaperORM\Cache\IndexCache;
use Michel\PaperORM\Cache\OneToManyCache;
use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Entity\TableMetadataInterface;
use Michel\PaperORM\Mapping\Entity;
use Michel\PaperORM\Mapping\Index;
use Michel\PaperORM\Proxy\ProxyInterface;

final class IndexMapper
{
    /**
     * @param $class
     * @return array<Index>
     */
    static public function getIndexes($class): array
    {
        $cache = IndexCache::getInstance();
        $key = is_object($class) ? get_class($class) : $class;
        $indexes = $cache->get($key);
        if (!empty($indexes)) {
            return $indexes;
        }
        $indexes = self::getIndexesMapping($class);
        $cache->set($key, $indexes);
        return $indexes;
    }

    static public function getIndexesMapping($class): array
    {
        if (!is_subclass_of($class, EntityInterface::class)) {
            throw new \LogicException(sprintf('%s must implement %s', $class, EntityInterface::class));
        }

        if (is_subclass_of($class, TableMetadataInterface::class)) {
            return $class::getIndexes();
        }

        if (PHP_VERSION_ID >= 80000) {
            $indexes = self::getIndexesPHP8($class);
            if (!empty($indexes)) {
                return $indexes;
            }

            if (!method_exists($class, 'getIndexes')) {
                return [];
            }
        }

        if (method_exists($class, 'getIndexes')) {
            return $class::getIndexes();
        }

        throw new \LogicException(sprintf(
            'Entity %s must define a Index via interface, attribute or static method',
            is_object($class) ? get_class($class) : $class
        ));
    }

    static private function getIndexesPHP8($class): array
    {
        if ($class instanceof ProxyInterface) {
            $class = $class->__getParentClass();
        }elseif (is_subclass_of($class, ProxyInterface::class)) {
            $reflector = new \ReflectionClass($class);
            $parentClass = $reflector->getParentClass();
            if ($parentClass) {
                $class = $parentClass->getName();
            }
        }

        $reflector = new \ReflectionClass($class);
        $attributes = $reflector->getAttributes(Index::class);
        $indexes = [];
        foreach ($attributes as $attribute) {
            $indexes[] = $attribute->newInstance();
        }
        return $indexes;
    }

}
