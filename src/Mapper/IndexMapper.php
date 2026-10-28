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
        if ($cache->has($key)) {
            return $cache->get($key);
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

        $indexes = self::getIndexAttributes($class);
        if (!empty($indexes)) {
            return $indexes;
        }

        if (method_exists($class, 'getIndexes')) {
            return $class::getIndexes();
        }

        return [];
    }

    static private function getIndexAttributes($class): array
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
