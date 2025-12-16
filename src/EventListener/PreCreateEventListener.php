<?php

namespace Michel\PaperORM\EventListener;

use Michel\PaperORM\Assigner\AutoIncrementAssigner;
use Michel\PaperORM\Assigner\SlugAssigner;
use Michel\PaperORM\Assigner\TimestampAssigner;
use Michel\PaperORM\Assigner\TokenAssigner;
use Michel\PaperORM\Assigner\UuidAssigner;

use Michel\PaperORM\Event\Create\PreCreateEvent;
use Michel\PaperORM\Mapper\ColumnMapper;
use Michel\PaperORM\Mapping\Column\AutoIncrementColumn;
use Michel\PaperORM\Mapping\Column\SlugColumn;
use Michel\PaperORM\Mapping\Column\TimestampColumn;
use Michel\PaperORM\Mapping\Column\TokenColumn;
use Michel\PaperORM\Mapping\Column\UuidColumn;

class PreCreateEventListener
{
    public function __invoke(PreCreateEvent $event)
    {
        $entity = $event->getEntity();
        $em = $event->getEm();

        $autoIncrementAssigner = new AutoIncrementAssigner($em->sequence());
        $slugAssigner = new SlugAssigner();
        $timestampAssigner = new TimestampAssigner();
        $uuidAssigner = new UuidAssigner();
        $tokenAssigner = new TokenAssigner();
        foreach (ColumnMapper::getColumns($entity) as $column) {
            if ($column instanceof TimestampColumn && $column->isOnCreated()) {
                $timestampAssigner->assign($entity, $column);
            } elseif ($column instanceof SlugColumn) {
                $slugAssigner->assign($entity, $column);
            } elseif ($column instanceof AutoIncrementColumn) {
                $autoIncrementAssigner->assign($entity, $column);
            }elseif ($column instanceof UuidColumn) {
                $uuidAssigner->assign($entity, $column);
            }elseif ($column instanceof TokenColumn) {
                $tokenAssigner->assign($entity, $column);
            }
        }
    }
}
