<?php

namespace Michel\PaperORM\Mapping\Column;

use Michel\PaperORM\Types\StringType;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class UuidColumn extends Column
{
    public function __construct(
        string $name = null,
        bool   $nullable = false,
        ?int $defaultValue = null
    )
    {
        parent::__construct('', $name, StringType::class, $nullable, $defaultValue, true, 36);
    }
}
