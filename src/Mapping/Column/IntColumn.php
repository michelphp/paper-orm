<?php

namespace Michel\PaperORM\Mapping\Column;

use Michel\PaperORM\Types\IntType;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class IntColumn extends Column
{
    public function __construct(
        string $name = null,
        bool   $nullable = false,
        ?int $defaultValue = null,
        bool   $unique = false
    )
    {
        parent::__construct('', $name, IntType::class, $nullable, $defaultValue, $unique);
    }
}
