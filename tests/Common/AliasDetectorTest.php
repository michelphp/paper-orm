<?php

namespace Test\Michel\PaperORM\Common;

use Michel\PaperORM\Query\AliasDetector;
use Michel\UniTester\TestCase;

class AliasDetectorTest extends TestCase
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
        $this->testSimpleQuery();
        $this->testComplexQuery();
    }

    private function testSimpleQuery()
    {
        $sql = "SELECT t.id, t.name FROM table t, table2 t2 WHERE t.id = t2.id";
        $expectedAliases = [
            't' => ['id', 'name'],
            't2' => ['id'],
        ];

        $result = AliasDetector::detect($sql);

        $this->assertEquals($expectedAliases, $result);
    }


    private function testComplexQuery()
    {
        $sql = "SELECT u.id, u.name AS user_name, o.amount, p.price, 'test.value' AS fake_column
FROM users u
JOIN orders o ON u.id = o.user_id
LEFT JOIN products p ON o.product_id = p.id
WHERE u.status = 'active' AND o.amount > 100
ORDER BY u.name;";
        $expectedAliases = [
            'u' => ['id', 'name', "status"],
            'o' => ['amount', 'user_id', 'product_id'],
            'p' => ['price', 'id'],
        ];
        $result = AliasDetector::detect($sql);
        $this->assertEquals($expectedAliases, $result);
    }
}
