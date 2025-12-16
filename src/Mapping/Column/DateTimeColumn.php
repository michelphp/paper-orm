<?php

namespace Michel\PaperORM\Mapping\Column;

use Attribute;
use Michel\PaperORM\Types\DateTimeType;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class DateTimeColumn extends Column
{

    public function __construct(
        string $name = null,
        bool   $nullable = false
    )
    {
        parent::__construct('', $name, DateTimeType::class, $nullable);
    }
}
