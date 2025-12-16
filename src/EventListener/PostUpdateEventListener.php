<?php

namespace Michel\PaperORM\EventListener;

use Michel\PaperORM\Assigner\AutoIncrementAssigner;
use Michel\PaperORM\Event\Update\PostUpdateEvent;
use Michel\PaperORM\Mapper\ColumnMapper;
use Michel\PaperORM\Mapping\Column\AutoIncrementColumn;

class PostUpdateEventListener
{
    public function __invoke(PostUpdateEvent $event)
    {
        $entity = $event->getEntity();
        $em = $event->getEm();

        $autoIncrementAssigner = new AutoIncrementAssigner($em->sequence());
        foreach (ColumnMapper::getColumns($entity) as $column) {
            if ($column instanceof AutoIncrementColumn) {
                $autoIncrementAssigner->commit($column);
            }
        }
    }
}
