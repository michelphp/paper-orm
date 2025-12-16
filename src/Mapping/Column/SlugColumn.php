<?php

namespace Michel\PaperORM\Mapping\Column;

use Attribute;
use Michel\PaperORM\Types\StringType;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class SlugColumn extends Column
{
    private array $from;
    private string $separator;

    public function __construct(
        string $name = null,
        array  $from = [],
        string $separator = '-',
        int    $length = 128,
        bool   $nullable = false,
        bool   $unique = true
    )
    {
        if (empty($separator)) {
            throw new \InvalidArgumentException('Slug separator cannot be empty.');
        }

        if (empty($from)) {
            throw new \InvalidArgumentException('Slug "fields" must reference at least one source column.');
        }

        parent::__construct('', $name, StringType::class, $nullable, null, $unique, $length);
        $this->from = $from;
        $this->separator = $separator;
    }

    public function getFrom(): array
    {
        return $this->from;
    }

    public function getSeparator(): string
    {
        return $this->separator;
    }
}
