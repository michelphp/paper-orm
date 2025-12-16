<?php

namespace Michel\PaperORM\Mapping\Column;

use Attribute;
use Michel\PaperORM\Types\BoolType;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class BoolColumn extends Column
{
    public function __construct(
        ?string $name = null,
        bool    $nullable = false,
        ?bool $defaultValue = null
    )
    {
        parent::__construct('', $name, BoolType::class, $nullable, $defaultValue);
    }
}
