<?php

namespace Test\Michel\PaperORM\Repository;

use Michel\PaperORM\Repository\Repository;
use Test\Michel\PaperORM\Entity\PostTest;
use Test\Michel\PaperORM\Entity\TagTest;

class TagTestRepository extends Repository
{
    public function getEntityName(): string
    {
        return TagTest::class;
    }
}
