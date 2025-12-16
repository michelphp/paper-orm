<?php

namespace Michel\PaperORM\Hydrator;

use LogicException;
use Michel\PaperORM\Cache\EntityMemcachedCache;
use Michel\PaperORM\Collection\ObjectStorage;
use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Mapper\ColumnMapper;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Mapping\OneToMany;
use Michel\PaperORM\Schema\SchemaInterface;
use ReflectionClass;

final class ArrayHydrator
{
    private SchemaInterface $schema;

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
        $columns = array_merge(ColumnMapper::getColumns($object), ColumnMapper::getOneToManyRelations($object));

        $result = [];
        foreach ($columns as $column) {
            if ($column instanceof OneToMany || $column instanceof JoinColumn) {
                $name = $column->getProperty();
            } else {
                $name = $column->getName();
            }
            if (!array_key_exists($name, $data)) {
                continue;
            }

            $value = $data[$name];
            $propertyName = $column->getProperty();
            if ($column instanceof JoinColumn) {
                if (!is_array($value) && $value !== null) {
                    $value = null;
                }
                $entityName = $column->getTargetEntity();
                if (is_array($value)) {
                    $value = $this->hydrate($entityName, $value);
                }
                $result[$propertyName] = $value;
                continue;
            } elseif ($column instanceof OneToMany) {
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
            $value =  $column->convertToPHP($value, $this->schema);
            if ($value instanceof \DateTimeInterface) {
                $value = $column->convertToDatabase($value, $this->schema);
            }
            $result[$propertyName] = $value;
        }
        return $result;
    }

}
