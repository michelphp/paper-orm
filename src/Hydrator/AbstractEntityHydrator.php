<?php

namespace Michel\PaperORM\Hydrator;

use Michel\PaperORM\Cache\PropertyReflectionCache;
use Michel\PaperORM\Collection\ObjectStorage;
use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Proxy\ProxyInterface;
use Michel\PaperORM\Schema\SchemaInterface;
use Michel\PaperORM\Types\Type;
use Michel\PaperORM\Types\TypeFactory;

abstract class AbstractEntityHydrator
{
    /**
     * @var array<string, Type>
     */
    private array $types = [];

    abstract protected function getSchema(): SchemaInterface;
    abstract protected function instantiate(string $class, array $data): object;

    /**
     *
     * @param object|string $objectOrClass
     * @param array $data
     * @return object
     */
    public function hydrate($objectOrClass, array $data): object
    {
        if (!is_subclass_of($objectOrClass, EntityInterface::class)) {
            throw new \LogicException(
                sprintf('Class %s must implement %s', $objectOrClass, EntityInterface::class)
            );
        }

        $object = is_string($objectOrClass) ? $this->instantiate($objectOrClass, $data) : $objectOrClass;

        $this->mapProperties($object, $data);

        return $object;
    }

    private function mapProperties(object $object, array $data): void
    {
        $class = get_class($object);
        $properties = [];

        foreach (HydrationPlan::get($class) as [$name, $kind, $column, $typeClass]) {
            if (!array_key_exists($name, $data)) {
                continue;
            }

            $value = $data[$name];
            if ($kind !== HydrationPlan::ONE_TO_MANY) {
                $properties[$column->getProperty()] = $column;
            }

            $property = PropertyReflectionCache::get($class, $column->getProperty());

            if ($kind === HydrationPlan::JOIN) {
                $entityName = $column->getTargetEntity();
                $value = is_array($value) ? $this->hydrate($entityName, $value) : null;
                $property->setValue($object, $value);
                continue;
            }

            if ($kind === HydrationPlan::ONE_TO_MANY) {
                $entityName = $column->getTargetEntity();
                $storage = $property->getValue($object) ?: new ObjectStorage();
                foreach ((array) $value as $item) {
                    $storage->add($this->hydrate($entityName, $item));
                }
                $property->setValue($object, $storage);
                continue;
            }

            $property->setValue($object, $typeClass === null ? $value : $this->getType($typeClass)->convertToPHP($value));
        }

        if ($object instanceof ProxyInterface) {
            $object->__setInitialized($properties);
        }
    }

    /**
     * Same instance as Column::convertToPHP() would use, looked up once per hydrator instead of once per value.
     */
    private function getType(string $typeClass): Type
    {
        return $this->types[$typeClass] ?? ($this->types[$typeClass] = TypeFactory::create($this->getSchema(), $typeClass));
    }
}

