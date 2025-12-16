<?php

namespace Test\Michel\PaperORM\Common;


use Michel\PaperORM\Debugger\SqlDebugger;
use Michel\UniTester\TestCase;

class SqlDebuggerTest extends TestCase
{
    protected function setUp(): void
    {
        // TODO: Implement setUp() method.
    }

    protected function tearDown(): void
    {
        // TODO: Implement tearDown() method.
    }

    protected function execute(): void
    {
        $this->testSqlDebuggerStartQuery();
        $this->testSqlDebugger();
    }

    public function testSqlDebuggerStartQuery()
    {
        $sqlDebugger = new SqlDebugger();
        $sqlDebugger->startQuery('SELECT * FROM users', []);
        $this->assertTrue(array_key_exists('startTime', $sqlDebugger->getQueries()[0]));
    }

    public function testSqlDebugger()
    {
        $sqlDebugger = new SqlDebugger();
        $sqlDebugger->startQuery('SELECT * FROM users', []);
        $sqlDebugger->stopQuery();
        $queries = $sqlDebugger->getQueries();
        $this->assertStrictEquals(1, count($queries));
        $this->assertEquals('[SELECT] SELECT * FROM users', $queries[0]['query']);
        $this->assertEquals([], $queries[0]['params']);
        $this->assertNotNull($queries[0]['executionTime']);
    }
}
