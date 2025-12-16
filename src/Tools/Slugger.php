<?php

namespace Michel\PaperORM\Tools;

final class Slugger
{
    public static function slugify(array $parts, string $separator = '-'): string
    {
        $parts = array_filter($parts, function ($part) {
            if ($part === null) {
                return false;
            }
            return trim($part) !== '';
        });

        if (empty($parts)) {
            throw new \InvalidArgumentException('Slug cannot be empty.');
        }

        $slug = implode(' ', $parts);
        $slug = trim($slug);
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9]+/', $separator, $slug);

        return trim($slug, $separator);
    }
}
