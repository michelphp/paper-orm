<?php

namespace Michel\PaperORM\EventListener;

use Michel\PaperORM\Assigner\AutoIncrementAssigner;
use Michel\PaperORM\Event\Create\PostCreateEvent;
use Michel\PaperORM\Mapper\ColumnMapper;
use Michel\PaperORM\Mapping\Column\AutoIncrementColumn;

class PostCreateEventListener
{
    public function __invoke(PostCreateEvent $event)
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
