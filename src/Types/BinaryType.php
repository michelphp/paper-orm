<?php

namespace Michel\PaperORM\Types;

final class BinaryType extends Type
{
    public function convertToDatabase($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        throw new \LogicException('Blob must be a binary string, got '.gettype($value));
    }

    public function convertToPHP($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        throw new \LogicException('Blob must be a binary string, got '.gettype($value));
    }
}
