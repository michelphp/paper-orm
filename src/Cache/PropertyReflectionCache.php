<?php

namespace Michel\PaperORM\Cache;

use LogicException;
use ReflectionClass;
use ReflectionProperty;

/**
 * One accessible ReflectionProperty per class and property, instead of one per hydrated object.
 */
final class PropertyReflectionCache
{
    /**
     * @var array<string, array<string, ReflectionProperty>>
     */
    private static array $properties = [];

    /**
     * Walks up the class hierarchy: the private properties of a parent class (an entity seen from its proxy,
     * or a base entity class) are not visible from the child class.
     *
     * @param object|string $objectOrClass
     */
    public static function get($objectOrClass, string $property): ReflectionProperty
    {
        $class = is_object($objectOrClass) ? get_class($objectOrClass) : $objectOrClass;
        if (isset(self::$properties[$class][$property])) {
            return self::$properties[$class][$property];
        }

        for ($reflection = new ReflectionClass($class); $reflection !== false; $reflection = $reflection->getParentClass()) {
            if ($reflection->hasProperty($property)) {
                $reflectionProperty = $reflection->getProperty($property);
                $reflectionProperty->setAccessible(true);
                return self::$properties[$class][$property] = $reflectionProperty;
            }
        }

        throw new LogicException(sprintf('Property %s not found in class %s or its parents', $property, $class));
    }
}
