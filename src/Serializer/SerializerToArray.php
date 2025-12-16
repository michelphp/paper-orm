<?php

namespace Michel\PaperORM\Serializer;

use LogicException;
use Michel\PaperORM\Mapper\ColumnMapper;
use Michel\PaperORM\Mapping\Column\DateTimeColumn;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Schema\SchemaInterface;
use ReflectionClass;
use ReflectionException;

final class SerializerToArray
{
    private object $entity;
    private SchemaInterface $schema;


    public function __construct(object $entity, SchemaInterface $schema)
    {
        $this->entity = $entity;
        $this->schema = $schema;
    }

    public function serialize(): array
    {
        $entity = $this->entity;
        $columns = ColumnMapper::getColumns(get_class($entity));
        if (count($columns) === 0) {
            return [];
        }

        $reflection = new ReflectionClass($entity);
        $data = [];
        foreach ($columns as $column) {
            try {
                if (false !== ($reflectionParent = $reflection->getParentClass()) && $reflectionParent->hasProperty($column->getProperty())) {
                    $property = $reflectionParent->getProperty($column->getProperty());
                }else {
                    $property = $reflection->getProperty($column->getProperty());
                }
            } catch (ReflectionException $e) {
                throw new LogicException("Property {$column->getProperty()} not found in class " . get_class($entity));
            }

            $property->setAccessible(true);
            $value = $property->getValue($entity);
            $propertyName = $column->getProperty();
            if (is_iterable($value) && !is_array($value)) {
                $data[$propertyName] = iterator_to_array($value);
                continue;
            }

            if ($column instanceof DateTimeColumn) {
                $data[$propertyName] = $column->convertToDatabase($value, $this->schema);
                continue;
            }

            if ($column instanceof JoinColumn) {
                $data[$propertyName] = (new self($value, $this->schema))->serialize();
                continue;
            }

            $data[$propertyName] = $property->getValue($entity);

        }
        return $data;
    }
}
