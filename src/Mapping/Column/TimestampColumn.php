<?php

namespace Michel\PaperORM\Mapping\Column;

use Attribute;
use Michel\PaperORM\Types\DateTimeType;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class TimestampColumn extends Column
{

    private bool $onCreated;
    private bool $onUpdated;
    public function __construct(
        string $name = null,
        bool $onCreated = false,
        bool $onUpdated = false,
        bool   $nullable = true
    )
    {
        if (!$onCreated && !$onUpdated) {
            throw new \InvalidArgumentException(
                'A TimestampColumn must be either onCreated or onUpdated (at least one true).'
            );
        }
        parent::__construct('', $name, DateTimeType::class, $nullable);
        $this->onCreated = $onCreated;
        $this->onUpdated = $onUpdated;
    }

    public function isOnCreated(): bool
    {
        return $this->onCreated;
    }

    public function isOnUpdated(): bool
    {
        return $this->onUpdated;
    }
}
