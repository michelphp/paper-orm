<?php

namespace Michel\PaperORM\Assigner;

use InvalidArgumentException;
use LogicException;
use Michel\PaperORM\Collection\ObjectStorage;
use Michel\PaperORM\Mapper\ColumnMapper;
use Michel\PaperORM\Mapping\Column\Column;
use Michel\PaperORM\Mapping\Column\SlugColumn;
use Michel\PaperORM\Tools\EntityAccessor;
use Michel\PaperORM\Tools\Slugger;

final class SlugAssigner implements ValueAssignerInterface
{
    public function assign(object $entity, Column $column): void
    {
        if (!$column instanceof SlugColumn) {
            throw new InvalidArgumentException(sprintf(
                'SlugAssigner::assign(): expected instance of %s, got %s.',
                SlugColumn::class,
                get_class($column)
            ));
        }
        if (EntityAccessor::getValue($entity, $column->getProperty()) !== null) {
            return;
        }

        $storage = new ObjectStorage(ColumnMapper::getColumns($entity));
        $from = $column->getFrom();
        $separator = $column->getSeparator();
        $values = [];
        foreach ($from as $field) {
            $col = $storage->findOneByMethod('getProperty', $field);
            if (!$col instanceof Column) {
                throw new LogicException(sprintf(
                    'Cannot set slug: expected column "%s" in entity "%s".',
                    $field,
                    get_class($entity)
                ));
            }
            $values[$field] = EntityAccessor::getValue($entity, $field);
        }
        EntityAccessor::setValue($entity, $column->getProperty(), Slugger::slugify($values, $separator));
    }

}
