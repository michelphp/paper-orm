<?php

namespace Michel\PaperORM\Pdo;

use Michel\PaperORM\Debugger\PDOStatementLogger;
use Michel\PaperORM\Debugger\SqlDebugger;
use Psr\Log\LoggerInterface;

final class PaperPDO extends \PDO
{
    private ?SqlDebugger $debug = null;

    public function enableSqlDebugger(?LoggerInterface $logger = null) : void
    {
        if ($this->debug === null) {
            $this->debug = new SqlDebugger($logger);
        }
        $this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [PDOStatementLogger::class, [$this->debug]]);
    }

    public function disableSqlDebugger() : void
    {
        $this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, null);
    }

    public function getSqlDebugger(): ?SqlDebugger
    {
        return $this->debug;
    }
}
