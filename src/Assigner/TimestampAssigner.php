<?php

namespace Michel\PaperORM\Assigner;

use DateTimeImmutable;
use InvalidArgumentException;
use Michel\PaperORM\Mapping\Column\Column;
use Michel\PaperORM\Mapping\Column\TimestampColumn;
use Michel\PaperORM\Tools\EntityAccessor;

final class TimestampAssigner implements ValueAssignerInterface
{
    public function assign(object $entity, Column $column): void
    {
        if (!$column instanceof TimestampColumn) {
            throw new InvalidArgumentException(sprintf(
                'TimestampAssigner::assign(): expected instance of %s, got %s.',
                TimestampColumn::class,
                get_class($column)
            ));
        }

        $property = $column->getProperty();
        EntityAccessor::setValue($entity, $property, new DateTimeImmutable('now'));
    }
}
