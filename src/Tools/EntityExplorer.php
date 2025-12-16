<?php

namespace Michel\PaperORM\Tools;

use Michel\FileSystem\Tools\FileExplorer;
use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Entity\SystemEntityInterface;

final class EntityExplorer
{

    /**
     * @param string[] $dirs
     * @return array{normal: string[], system: string[]}
     */
    public static function getEntities(array $dirs): array
    {
        $normal = [];
        $system = [];

        foreach ($dirs as $dir) {
            $explorer = new FileExplorer($dir);
            $files = $explorer->searchByExtension('php', true);

            foreach ($files as $file) {
                $entityClass = self::extractNamespaceAndClass($file['path']);
                if ($entityClass === null || !class_exists($entityClass)) {
                    continue;
                }
                if (!is_subclass_of($entityClass, EntityInterface::class)) {
                    continue;
                }

                if (is_subclass_of($entityClass, SystemEntityInterface::class)) {
                    $system[$file['path']] = $entityClass;
                } else {
                    $normal[$file['path']] = $entityClass;
                }
            }
        }

        return [
            'normal' => $normal,
            'system' => $system,
        ];
    }

    private static function extractNamespaceAndClass(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException('File not found: ' . $filePath);
        }

        $contents = file_get_contents($filePath);
        $namespace = '';
        $class = '';
        $isExtractingNamespace = false;
        $isExtractingClass = false;

        foreach (token_get_all($contents) as $token) {
            if (is_array($token) && $token[0] == T_NAMESPACE) {
                $isExtractingNamespace = true;
            }

            if (is_array($token) && $token[0] == T_CLASS) {
                $isExtractingClass = true;
            }

            if ($isExtractingNamespace) {
                if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR,  265 /* T_NAME_QUALIFIED For PHP 8*/])) {
                    $namespace .= $token[1];
                } else if ($token === ';') {
                    $isExtractingNamespace = false;
                }
            }

            if ($isExtractingClass) {
                if (is_array($token) && $token[0] == T_STRING) {
                    $class = $token[1];
                    break;
                }
            }
        }

        if (empty($class)) {
            return null;
        }

        $fullClass = $namespace ? $namespace . '\\' . $class : $class;
        if (class_exists($fullClass) && is_subclass_of($fullClass, EntityInterface::class)) {
            return $fullClass;
        }

        return null;
    }

}
