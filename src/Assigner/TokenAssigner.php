<?php

namespace Michel\PaperORM\Assigner;

use Michel\PaperORM\Mapping\Column\Column;
use Michel\PaperORM\Mapping\Column\SlugColumn;
use Michel\PaperORM\Mapping\Column\TokenColumn;
use Michel\PaperORM\Mapping\Column\UuidColumn;
use Michel\PaperORM\Tools\EntityAccessor;
use Michel\PaperORM\Tools\IDBuilder;

final class TokenAssigner implements ValueAssignerInterface
{
    public function assign(object $entity, Column $column): void
    {
        if (!$column instanceof TokenColumn) {
            throw new \InvalidArgumentException(sprintf(
                'TokenAssigner::assign(): expected instance of %s, got %s.',
                TokenColumn::class,
                get_class($column)
            ));
        }

        $property = $column->getProperty();
        EntityAccessor::setValue($entity, $property, IDBuilder::generate(sprintf("{TOKEN%s}", $column->getLength())));
    }
}
