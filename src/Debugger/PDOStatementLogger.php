<?php

namespace Michel\PaperORM\Debugger;

class PDOStatementLogger extends \PDOStatement
{
    private SqlDebugger $debugger;
    private array $boundParams = [];
    protected function __construct(SqlDebugger $debugger)
    {
        $this->debugger = $debugger;
    }

    public function execute($params = null): bool
    {
        $this->startQuery($this->queryString, $params ?: $this->boundParams);
        $result = parent::execute($params);
        $this->stopQuery();
        return $result;
    }

    public function bindValue($param, $value, $type = \PDO::PARAM_STR): bool
    {
        $this->boundParams[$param] = $value;
        return parent::bindValue($param, $value, $type);
    }

    private function startQuery(string $query, array $params): void
    {
        $this->getSqlDebugger()->startQuery($query , $params);
    }

    private function stopQuery(): void
    {
        $this->getSqlDebugger()->stopQuery();
    }

    public function getSqlDebugger(): SqlDebugger
    {
        return $this->debugger;
    }
}
