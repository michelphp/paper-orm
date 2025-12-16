<?php

namespace Michel\PaperORM\Query;

final class AliasGenerator
{
    private array $usedAliases = [];


    public function generateAlias(string $entity): string
    {
        $entityName = basename(str_replace('\\', '/', $entity));
        $alias = strtolower(substr($entityName, 0, 1));
        if (in_array($alias, $this->usedAliases)) {
            $suffix = 1;
            while (in_array($alias . $suffix, $this->usedAliases)) {
                $suffix++;
            }
            $alias .= $suffix;
        }
        $this->usedAliases[] = $alias;

        return $alias;
    }
}
