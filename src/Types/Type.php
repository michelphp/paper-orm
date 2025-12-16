<?php

namespace Michel\PaperORM\Types;

use Michel\PaperORM\Schema\SchemaInterface;

abstract class Type
{
    private SchemaInterface $schema;

    final public function __construct(SchemaInterface $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    abstract public function convertToDatabase($value);

    /**
     * @param mixed $value
     * @return mixed
     */
    abstract public function convertToPHP($value);


    final protected function getSchema(): SchemaInterface
    {
        return $this->schema;
    }

}
