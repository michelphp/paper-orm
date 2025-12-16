<?php

namespace Michel\PaperORM\EventListener;

use Michel\PaperORM\Assigner\TimestampAssigner;
use Michel\PaperORM\Event\Update\PreUpdateEvent;
use Michel\PaperORM\Mapper\ColumnMapper;
use Michel\PaperORM\Mapping\Column\TimestampColumn;

class PreUpdateEventListener
{

    public function __invoke(PreUpdateEvent $event)
    {
        $entity = $event->getEntity();
        $timestampAssigner = new TimestampAssigner();
        foreach (ColumnMapper::getColumns($entity) as $column) {
            if ($column instanceof TimestampColumn && $column->isOnUpdated()) {
                $timestampAssigner->assign($entity, $column);
            }
        }
    }
}
