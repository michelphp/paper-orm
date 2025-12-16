<?php

namespace Michel\PaperORM\Driver;

use Exception;
use Michel\PaperORM\PaperConnection;
use Michel\PaperORM\Platform\MariaDBPlatform;

final class DriverManager
{

    private function __construct()
    {
    }

    private static array $driverSchemeAliases = [
        'sqlite' => SqliteDriver::class,
        'sqlite3' => SqliteDriver::class,
        'mysql' => MariaDBDriver::class,
        'mariadb' => MariaDBDriver::class
    ];

    public static function createConnection(string $driver, array $params): PaperConnection
    {
        $driver = strtolower($driver);

        $drivers = self::$driverSchemeAliases;
        if (isset($params['options']['driverClass'])) {
            $drivers[$driver] = $params['options']['driverClass'];
            unset($params['options']['driverClass']);
        }

        if (!isset($drivers[$driver])) {
            throw new Exception('Driver not found, please check your config : ' . $driver);
        }

        $driver = $drivers[$driver];
        $driver = new $driver();
        return new PaperConnection($driver, $params);
    }

}
