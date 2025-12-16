<?php

namespace Michel\PaperORM\Mapping\Column;

use Michel\PaperORM\Types\FloatType;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class FloatColumn extends Column
{

    public function __construct(
        string $name = null,
        bool   $nullable = false,
        ?float $defaultValue = null,
        bool   $unique = false
    )
    {
        parent::__construct('', $name, FloatType::class, $nullable, $defaultValue, $unique);
    }
}
