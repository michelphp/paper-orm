<?php

namespace Michel\PaperORM;

use Exception;
use LogicException;
use PDO;
use PDOStatement;
use Michel\PaperORM\Driver\DriverInterface;
use Michel\PaperORM\Pdo\PaperPDO;
use Psr\Log\LoggerInterface;

final class PaperConnection
{
    private ?PaperPDO $pdo = null;

    private array $params;

    private DriverInterface $driver;

    private bool $debug;
    private ?LoggerInterface $logger = null;

    /**
     * Prepared write statements, reused when the same SQL comes back (e.g. the updates of one flush()).
     *
     * @var array<string, PDOStatement>
     */
    private array $statements = [];
    private const MAX_PREPARED_STATEMENTS = 100;

    /**
     * Built queries, by shape (see QueryBuilder::buildSqlQuery()). They hold the PDO connection, so they live
     * and die with it.
     *
     * @var array<string, object>
     */
    private array $queries = [];
    private const MAX_QUERIES = 256;

    public function __construct(DriverInterface $driver, array $params)
    {
        $this->params = $params;
        $this->driver = $driver;
        $extra = $params['extra'] ?? [];
        $this->debug = (bool)($extra['debug'] ?? false);
    }


    public function executeStatement(string $query, array $params = []): int
    {
        // Only statements with parameters are reused: they return no rows, so no open cursor can be shared,
        // and they are the ones repeated with other values. Schema statements are prepared each time.
        if ($params === []) {
            return $this->executeQuery($query)->rowCount();
        }

        $db = $this->statements[$query] ?? null;
        if ($db === null) {
            if (count($this->statements) >= self::MAX_PREPARED_STATEMENTS) {
                array_shift($this->statements);
            }
            $db = $this->statements[$query] = $this->prepare($query);
        }
        $this->bindAndExecute($db, $params);
        return $db->rowCount();
    }

    public function executeQuery(string $query, array $params = []): PDOStatement
    {
        $db = $this->prepare($query);
        $this->bindAndExecute($db, $params);
        return $db;
    }

    private function prepare(string $query): PDOStatement
    {
        $db = $this->getPdo()->prepare($query);
        if ($db === false) {
            throw new Exception($this->getPdo()->errorInfo()[2]);
        }
        return $db;
    }

    private function bindAndExecute(PDOStatement $db, array $params): void
    {
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $db->bindValue(':' . $key, $value);
            } else {
                $db->bindValue($key + 1, $value);
            }
        }
        $db->execute();
    }

    public function fetch(string $query, array $params = []): ?array
    {
        $db = $this->executeQuery($query, $params);
        $data = $db->fetch(PDO::FETCH_ASSOC);
        return $data === false ? null : $data;
    }

    public function fetchAll(string $query, array $params = []): array
    {
        $db = $this->executeQuery($query, $params);
        return $db->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    public function getPdo(): PaperPDO
    {
        $this->connect();
        return $this->pdo;
    }

    public function connect(): bool
    {
        if (!$this->isConnected()) {
            $params = $this->params;
            unset($params['extra']);
            $this->pdo = $this->driver->connect($this->params);
            if ($this->debug) {
                $this->pdo->enableSqlDebugger($this->logger);
            }
            return true;
        }

        return false;
    }

    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    public function getCachedQuery(string $key): ?object
    {
        return $this->queries[$key] ?? null;
    }

    public function cacheQuery(string $key, object $query): void
    {
        if (count($this->queries) >= self::MAX_QUERIES) {
            // Drop the oldest one, in constant time.
            unset($this->queries[array_key_first($this->queries)]);
        }
        $this->queries[$key] = $query;
    }

    public function close(): void
    {
        // The prepared statements and the built queries keep a reference to the PDO connection: release them too.
        $this->statements = [];
        $this->queries = [];
        $this->pdo = null;
    }

    public function cloneConnectionWithoutDbname(): self
    {
        $params = $this->params;
        unset($params['path']);
        return new self($this->driver, $params);
    }

    public function withLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }
}
