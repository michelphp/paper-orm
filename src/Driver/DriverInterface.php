<?php

namespace Michel\PaperORM\Driver;

use Michel\PaperORM\PaperConnection;
use Michel\PaperORM\Pdo\PaperPDO;
use Michel\PaperORM\Platform\PlatformInterface;
use Michel\PaperORM\Schema\SchemaInterface;

interface DriverInterface
{
    public function connect(array $params): PaperPDO;
    public function createDatabasePlatform(PaperConnection $connection): PlatformInterface;
    public function createDatabaseSchema(): SchemaInterface;
}
