<?php

namespace Michel\PaperORM\Mapping\Column;

use Attribute;
use Michel\PaperORM\Types\StringType;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class StringColumn extends Column
{
    public function __construct(
        string $name = null,
        int $length = 255,
        bool   $nullable = false,
        string $defaultValue = null,
        bool $unique = false
    )
    {
        parent::__construct('', $name, StringType::class, $nullable, $defaultValue, $unique, $length);
    }

}
