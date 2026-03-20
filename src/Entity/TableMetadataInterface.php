<?php

namespace Michel\PaperORM\Entity;

use Michel\PaperORM\Mapping\Index;

interface TableMetadataInterface
{
    static public function getTableName(): string;

    /**
     * @return array<Index>
     */
    static public function getIndexes(): array;
    static public function getRepositoryName(): ?string;
    static public function columnsMapping(): array;
}
