<?php

namespace Michel\PaperORM\Assigner;

use Michel\PaperORM\Mapping\Column\Column;

interface ValueAssignerInterface
{
    public function assign(object $entity, Column $column): void;
}
