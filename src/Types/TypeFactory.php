<?php

namespace Michel\PaperORM\Types;

use Michel\PaperORM\Schema\SchemaInterface;

final class TypeFactory
{
    /**
     * @param SchemaInterface $schema
     * @param string $typeClass
     * @return Type
     * @throws \ReflectionException
     */
    public static function create(SchemaInterface $schema, string $typeClass): Type
    {
        $type = (new \ReflectionClass($typeClass))->newInstance($schema);
        if (!$type instanceof Type) {
            throw new \InvalidArgumentException($typeClass. ' must be an instance of '.Type::class);
        }
        return $type;
    }
}
