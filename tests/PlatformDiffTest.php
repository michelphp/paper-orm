<?php

namespace Test\Michel\PaperORM;

use Michel\PaperORM\EntityManager;
use Michel\PaperORM\Mapping\Column\BoolColumn;
use Michel\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Michel\PaperORM\Mapping\Column\StringColumn;
use Michel\PaperORM\PaperConfiguration;
use Michel\UniTester\TestCase;
use Test\Michel\PaperORM\Helper\DataBaseHelperTest;

class PlatformDiffTest extends TestCase
{

    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }

    protected function execute(): void
    {
        foreach (DataBaseHelperTest::drivers() as $name => $params) {
            $em = EntityManager::createFromConfig(PaperConfiguration::fromArray($params));
            $this->executeTest($em);
            $em->getConnection()->close();
        }
    }

    private function executeTest(EntityManager  $em)
    {
        $platform = $em->getPlatform();
        $platform->createDatabaseIfNotExists();
        $platform->dropDatabase();
        $platform->createDatabaseIfNotExists();
        $columns = [
            new PrimaryKeyColumn('id'),
            new StringColumn('firstname'),
            new StringColumn('lastname'),
            new StringColumn('email'),
            new StringColumn('password'),
            (new BoolColumn('is_active'))->bindProperty('active'),
        ];
        $rows = $platform->createTable('user', $columns);
        $diff = $platform->diff('user', $columns, [] );
        $this->assertEmpty($diff->getColumnsToAdd());
        $this->assertEmpty($diff->getColumnsToUpdate());
        $this->assertEmpty($diff->getColumnsToDelete());

        $columns[3] = new StringColumn('username');

        $diff = $platform->diff('user', $columns, [] );

        $this->assertTrue(count($diff->getColumnsToAdd()) == 1);
        $this->assertTrue(count($diff->getColumnsToDelete()) == 1);
        $this->assertEmpty($diff->getColumnsToUpdate());

        $platform->dropTable('user');
        $platform->createTable('user', $columns, [] );

        $columns[3] = new StringColumn( 'username', 100);
        $diff = $platform->diff('user', $columns, [] );

        $this->assertTrue(count($diff->getColumnsToUpdate()) == 1);
        $this->assertEmpty($diff->getColumnsToAdd());
        $this->assertEmpty($diff->getColumnsToDelete());


        $diff = $platform->diff('user2', $columns, [] );
        $this->assertTrue(count($diff->getColumnsToAdd()) == 6);

    }
}
