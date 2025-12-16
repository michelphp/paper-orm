<?php

namespace Michel\PaperORM\Assigner;

use Michel\PaperORM\Mapping\Column\Column;
use Michel\PaperORM\Mapping\Column\SlugColumn;
use Michel\PaperORM\Mapping\Column\UuidColumn;
use Michel\PaperORM\Tools\EntityAccessor;
use Michel\PaperORM\Tools\IDBuilder;

final class UuidAssigner implements ValueAssignerInterface
{
    public function assign(object $entity, Column $column): void
    {
        if (!$column instanceof UuidColumn) {
            throw new \InvalidArgumentException(sprintf(
                'UuidAssigner::assign(): expected instance of %s, got %s.',
                UuidColumn::class,
                get_class($column)
            ));
        }

        $property = $column->getProperty();
        EntityAccessor::setValue($entity, $property, IDBuilder::generate('{UUID}'));
    }
}
