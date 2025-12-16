<?php

namespace Michel\PaperORM\Mapping\Column;

use Michel\PaperORM\Types\StringType;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class TokenColumn extends Column
{

    private int $length;
    public function __construct(
        string $name = null,
        int    $length = 128,
        bool   $nullable = false,
        ?int $defaultValue = null
    )
    {
        if (!in_array($length, [16, 32, 64, 128])) {
            throw new \InvalidArgumentException(sprintf(
                'Token length must be 16, 32, 64 or 128, got %s.',
                $length
            ));
        }
        parent::__construct('', $name, StringType::class, $nullable, $defaultValue, true,$length);

        $this->length = $length;
    }

    public function getLength(): int
    {
        return $this->length;
    }
}
