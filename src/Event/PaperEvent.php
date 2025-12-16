<?php

namespace Michel\PaperORM\Event;

use Michel\EventDispatcher\Event;
use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\EntityManagerInterface;

abstract class PaperEvent extends Event
{

    private EntityManagerInterface $em;
    private EntityInterface $entity;

    /**
     * PreCreateEvent constructor.
     *
     * @param EntityManagerInterface $em
     * @param EntityInterface $entity
     */
    public function __construct(EntityManagerInterface $em, EntityInterface $entity)
    {
        $this->entity = $entity;
        $this->em = $em;
    }


    public function getEntity(): EntityInterface
    {
        return $this->entity;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEm(): EntityManagerInterface
    {
        return $this->em;
    }

}
