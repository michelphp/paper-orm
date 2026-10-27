<?php

namespace Michel\PaperORM\Mapper;


use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Entity\TableMetadataInterface;
use Michel\PaperORM\Mapping\Entity;
use Michel\PaperORM\Proxy\ProxyInterface;

final class EntityMapper
{
    /**
     * Table and repository names never change at runtime, they are resolved once per class
     * (the attribute lookup creates a ReflectionClass and an attribute instance).
     *
     * @var array<string, string>
     */
    private static array $tables = [];

    /**
     * @var array<string, string|null>
     */
    private static array $repositories = [];

    static public function getTable($class): string
    {
        $key = is_object($class) ? get_class($class) : $class;
        if (!isset(self::$tables[$key])) {
            self::$tables[$key] = self::resolveTable($class);
        }
        return self::$tables[$key];
    }

    static public function getRepositoryName($class): ?string
    {
        $key = is_object($class) ? get_class($class) : $class;
        if (!array_key_exists($key, self::$repositories)) {
            self::$repositories[$key] = self::resolveRepositoryName($class);
        }
        return self::$repositories[$key];
    }

    static private function resolveTable($class): string
    {
        if (!is_subclass_of($class, EntityInterface::class)) {
            throw new \LogicException(sprintf('%s must implement %s', $class, EntityInterface::class));
        }

        if (is_subclass_of($class, TableMetadataInterface::class)) {
            return $class::getTableName();
        }

        if (PHP_VERSION_ID >= 80000) {
            $entity = self::getEntityPHP8($class);
            if ($entity instanceof Entity) {
                return $entity->getTable();
            }
        }

        if (method_exists($class, 'getTableName')) {
            return $class::getTableName();
        }

        throw new \LogicException(sprintf(
            'Entity %s must define a entityName via interface, attribute or static method',
            is_object($class) ? get_class($class) : $class
        ));
    }

    static private function resolveRepositoryName($class): ?string
    {
        if (!is_subclass_of($class, EntityInterface::class)) {
            throw new \LogicException(sprintf('%s must implement %s', $class, EntityInterface::class));
        }

        if (is_subclass_of($class, TableMetadataInterface::class)) {
            return $class::getRepositoryName();
        }

        if (PHP_VERSION_ID >= 80000) {
            $entity = self::getEntityPHP8($class);
            if ($entity instanceof Entity) {
                return $entity->getRepositoryClass();
            }
        }

        if (method_exists($class, 'getRepositoryName')) {
            return $class::getRepositoryName();
        }

        throw new \LogicException(sprintf(
            'Entity %s must define a repository via interface, attribute or static method',
            is_object($class) ? get_class($class) : $class
        ));
    }

    static private function getEntityPHP8($class): ?Entity
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
        $attributes = $reflector->getAttributes(Entity::class);
        if (!empty($attributes)) {
            /** @var \Michel\PaperORM\Mapping\Entity $instance */
            return $attributes[0]->newInstance();
        }

        return null;
    }
}
