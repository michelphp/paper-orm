<?php

namespace Michel\PaperORM;


final class UnitOfWork
{

    /**
     * A list of all pending entity insertions.
     *
     * @psalm-var array<int, object>
     */
    private array $entityInsertions = [];

    /**
     * A list of all pending entity updates.
     *
     * @psalm-var array<int, object>
     */
    private array $entityUpdates = [];

    /**
     * A list of all pending entity deletions.
     *
     * @psalm-var array<int, object>
     */
    private array $entityDeletions = [];

    public function getEntityInsertions(): array
    {
        return $this->entityInsertions;
    }

    public function getEntityUpdates(): array
    {
        return $this->entityUpdates;
    }

    public function getEntityDeletions(): array
    {
        return $this->entityDeletions;
    }

    public function persist(object $entity): void
    {
        $this->unsetEntity($entity);

        $id = spl_object_id($entity);
        if (!$entity->getPrimaryKeyValue()) {
            $this->entityInsertions[$id] = $entity;
            return;
        }

        $this->entityUpdates[$id] = $entity;
    }

    public function remove(object $entity): void
    {
        $this->unsetEntity($entity);

        $id = spl_object_id($entity);
        $this->entityDeletions[$id] = $entity;
    }

    public function unsetEntity(object $entity): void
    {
        $id = spl_object_id($entity);
        if (isset($this->entityUpdates[$id])) {
            unset($this->entityUpdates[$id]);
        }
        if (isset($this->entityInsertions[$id])) {
            unset($this->entityInsertions[$id]);
        }
        if (isset($this->entityDeletions[$id])) {
            unset($this->entityDeletions[$id]);
        }
    }
     public function clear(): void
     {
         $this->entityInsertions = [];
         $this->entityUpdates = [];
         $this->entityDeletions = [];
     }
}
