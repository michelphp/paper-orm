<?php

namespace Michel\PaperORM\Driver;

use PDO;
use Michel\PaperORM\PaperConnection;
use Michel\PaperORM\Pdo\PaperPDO;
use Michel\PaperORM\Platform\MariaDBPlatform;
use Michel\PaperORM\Platform\PlatformInterface;
use Michel\PaperORM\Platform\SqlitePlatform;
use Michel\PaperORM\Schema\MariaDBSchema;
use Michel\PaperORM\Schema\SqliteSchema;

final class MariaDBDriver implements DriverInterface
{
    public function connect(
        #[SensitiveParameter]
        array $params
    ): PaperPDO
    {
        $driverOptions = $params['driverOptions'] ?? [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];
        if (!empty($params['persistent'])) {
            $driverOptions[PDO::ATTR_PERSISTENT] = true;
        }

        return new PaperPDO(
            $this->constructPdoDsn($params),
            $params['user'] ?? '',
            $params['password'] ?? '',
            $driverOptions,
        );
    }

    /**
     * Constructs the Sqlite PDO DSN.
     *
     * @param array<string, mixed> $params
     */
    private function constructPdoDsn(array $params): string
    {
        $dsn = 'mysql:';
        if (isset($params['host']) && $params['host'] !== '') {
            $dsn .= 'host=' . $params['host'] . ';';
        }

        if (isset($params['port'])) {
            $dsn .= 'port=' . $params['port'] . ';';
        }

        if (isset($params['dbname'])) {
            $dsn .= 'dbname=' . $params['dbname'] . ';';
        }elseif (isset($params['path'])) {
            $dsn .= 'dbname=' . $params['path'] . ';';
        }

        if (isset($params['unix_socket'])) {
            $dsn .= 'unix_socket=' . $params['unix_socket'] . ';';
        }

        if (isset($params['options']['charset'])) {
            $dsn .= sprintf('charset=%s;', $params['options']['charset']);
        }

        return $dsn;
    }

    public function createDatabasePlatform(PaperConnection $connection): PlatformInterface
    {
        return new MariaDBPlatform($connection, $this->createDatabaseSchema());
    }

    public function createDatabaseSchema(): MariaDBSchema
    {
        return new MariaDBSchema();
    }
}
