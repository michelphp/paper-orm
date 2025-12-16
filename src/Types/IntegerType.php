<?php

namespace Michel\PaperORM\Types;

final class IntegerType extends Type
{

    public function convertToDatabase($value): ?int
    {
        return $value === null ? null : (int)$value;
    }

    public function convertToPHP($value): ?int
    {
        return $value === null ? null : (int)$value;
    }
}
