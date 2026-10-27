<?php

namespace Michel\PaperORM\Hydrator;

use LogicException;
use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Schema\SchemaInterface;
use Michel\PaperORM\Types\Type;
use Michel\PaperORM\Types\TypeFactory;

final class ArrayHydrator
{
    private SchemaInterface $schema;

    /**
     * @var array<string, Type>
     */
    private array $types = [];

    public function __construct(SchemaInterface $schema)
    {
        $this->schema = $schema;
    }

    public function hydrate(string $object, array $data): array
    {
        if (!class_exists($object)) {
            throw new LogicException('Class ' . $object . ' does not exist');
        }
        if (!is_subclass_of($object, EntityInterface::class)) {
            throw new LogicException('Class ' . $object . ' is not an Michel\PaperORM\Entity\EntityInterface');
        }
        $result = [];
        foreach (HydrationPlan::get($object) as [$name, $kind, $column, $typeClass]) {
            if (!array_key_exists($name, $data)) {
                continue;
            }

            $value = $data[$name];
            $propertyName = $column->getProperty();
            if ($kind === HydrationPlan::JOIN) {
                if (!is_array($value) && $value !== null) {
                    $value = null;
                }
                $entityName = $column->getTargetEntity();
                if (is_array($value)) {
                    $value = $this->hydrate($entityName, $value);
                }
                $result[$propertyName] = $value;
                continue;
            } elseif ($kind === HydrationPlan::ONE_TO_MANY) {
                if (!is_array($value)) {
                    $value = [];
                }
                $entityName = $column->getTargetEntity();
                $storage = [];
                foreach ($value as $item) {
                    $storage[] = $this->hydrate($entityName, $item);
                }
                $result[$propertyName] = $storage;
                unset($storage);
                continue;
            }
            if ($typeClass !== null) {
                $type = $this->getType($typeClass);
                $value = $type->convertToPHP($value);
                if ($value instanceof \DateTimeInterface) {
                    $value = $type->convertToDatabase($value);
                }
            }
            $result[$propertyName] = $value;
        }
        return $result;
    }

    /**
     * Same instance as Column::convertToPHP() would use, looked up once per hydrator instead of once per value.
     */
    private function getType(string $typeClass): Type
    {
        return $this->types[$typeClass] ?? ($this->types[$typeClass] = TypeFactory::create($this->schema, $typeClass));
    }

}
