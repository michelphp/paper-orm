<?php

namespace Michel\PaperORM\Hydrator;

use Michel\PaperORM\Schema\SchemaInterface;

final class ReadOnlyEntityHydrator extends AbstractEntityHydrator
{

    private SchemaInterface $schema;

    public function __construct(SchemaInterface $schema)
    {
        $this->schema = $schema;
    }
    protected function instantiate(string $class, array $data): object
    {
        return new $class();
    }

    protected function getSchema(): SchemaInterface
    {
        return $this->schema;
    }
}
