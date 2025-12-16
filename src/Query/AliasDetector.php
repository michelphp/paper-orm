<?php

namespace Michel\PaperORM\Query;

final class AliasDetector
{

    public static function detect(string $sql): array
    {
        $sql = str_replace(PHP_EOL, ' ', $sql);
        $tokens = explode(' ', str_replace([",", "(", ")", ';'], " ", $sql));
        $aliases = [];

        foreach ($tokens as $token) {
            if (strpos($token, '.') !== false && $token[0] !== "'" && $token[0] !== '"') {
                $parts = explode('.', $token, 2);
                if (count($parts) === 2) {
                    $aliases[$parts[0]][] = $parts[1];
                }
            }
        }

        foreach ($aliases as $alias => $columns) {
            $aliases[$alias] = array_values(array_unique($columns));
        }
        return $aliases;
    }

}
