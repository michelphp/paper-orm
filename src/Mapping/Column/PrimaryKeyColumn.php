<?php

namespace Michel\PaperORM\Mapping\Column;

use Michel\PaperORM\Types\IntegerType;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class PrimaryKeyColumn extends Column
{
    public function __construct(string $name = null, string $type = IntegerType::class)
    {
        parent::__construct('', $name, $type);
    }
}
