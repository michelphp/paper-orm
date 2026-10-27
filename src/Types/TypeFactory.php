<?php

namespace Michel\PaperORM\Types;

use Michel\PaperORM\Schema\SchemaInterface;

final class TypeFactory
{
    /**
     * Types are stateless: one instance per schema and type class is shared by every conversion.
     *
     * @var \SplObjectStorage<SchemaInterface, array<string, Type>>|null
     */
    private static ?\SplObjectStorage $instances = null;

    /**
     * @param SchemaInterface $schema
     * @param string $typeClass
     * @return Type
     */
    public static function create(SchemaInterface $schema, string $typeClass): Type
    {
        if (self::$instances === null) {
            self::$instances = new \SplObjectStorage();
        }

        $types = self::$instances->contains($schema) ? self::$instances[$schema] : [];
        if (!isset($types[$typeClass])) {
            $type = new $typeClass($schema);
            if (!$type instanceof Type) {
                throw new \InvalidArgumentException($typeClass. ' must be an instance of '.Type::class);
            }
            $types[$typeClass] = $type;
            self::$instances[$schema] = $types;
        }

        return $types[$typeClass];
    }
}
