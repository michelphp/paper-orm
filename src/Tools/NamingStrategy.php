<?php

namespace Michel\PaperORM\Tools;

class NamingStrategy
{

    public static function toSnakeCase(string $input): string
    {
        $input = preg_replace('/(\p{Lu})(\p{Lu}\p{Ll})/u', '$1_$2', $input);
        $input = preg_replace('/(?<=\p{Ll}|\d)(\p{Lu})/u', '_$1', $input);
        $input = preg_replace('/(\p{Lu})(?=\d)/u', '$1_', $input);

        return strtolower($input);
    }

}
