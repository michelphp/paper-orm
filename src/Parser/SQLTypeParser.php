<?php

namespace Michel\PaperORM\Parser;

final class SQLTypeParser
{
    public static function extractParenthesesValues(string $sqlType): array
    {
        $result = sscanf($sqlType, '%*[^(](%[^)]', $inside);

        if ($result === 0 || !isset($inside)) {
            return [];
        }

        return array_map('trim', explode(',', $inside));
    }

    public static function getBaseType(string $sqlType): string
    {
        $pos = strpos($sqlType, '(');
        return $pos === false ? $sqlType : substr(strtoupper($sqlType), 0, $pos);
    }

    public static function hasParentheses(string $sqlType): bool
    {
        return str_contains($sqlType, '(') && str_contains($sqlType, ')');
    }

    public static function extractTypedParameters(string $sqlType): array
    {
        $values = self::extractParenthesesValues($sqlType);

        return array_map(function($value) {
            if (is_numeric($value)) {
                return str_contains($value, '.') ? (float)$value : (int)$value;
            }
            return $value;
        }, $values);
    }

}
