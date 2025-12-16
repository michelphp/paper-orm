<?php

namespace Michel\PaperORM\Mapping\Column;

use Attribute;
use Michel\PaperORM\Types\StringType;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class AutoIncrementColumn extends Column
{
    private string $key;
    private int $pad;
    private ?string $prefix;
    public function __construct(
        string  $name = null,
        string $key = null,
        int     $pad = 6,
        ?string $prefix = null,
        bool    $nullable = false
    )
    {
        if ($pad < 1) {
            throw new \InvalidArgumentException('AutoIncrementColumn : pad must be at least 1.');
        }

        if (empty($key)) {
            throw new \InvalidArgumentException(
                'AutoIncrementColumn configuration error: A non-empty key (sequence or table.sequence) must be defined.'
            );
        }

        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $key)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid key or sequence name "%s": only alphanumeric characters, underscores (_), and dots (.) are allowed.',
                $key
            ));
        }

        $length = strlen($prefix) + $pad;
        parent::__construct('', $name, StringType::class, $nullable, null, true, $length);

        $this->pad = $pad;
        $this->prefix = $prefix;
        $this->key = $key;
    }

    public function getPad(): int
    {
        return $this->pad;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }
    public function getKey(): string
    {
        return $this->key;
    }
}
