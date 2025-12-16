<?php

namespace Michel\PaperORM\Assigner;

use Michel\PaperORM\Mapping\Column\Column;

interface CommitAssignerInterface
{
    public function commit(Column $column): void;
}
