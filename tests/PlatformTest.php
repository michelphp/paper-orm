<?php

namespace Test\Michel\PaperORM;

use Michel\PaperORM\EntityManager;
use Michel\PaperORM\Mapping\Column\BoolColumn;
use Michel\PaperORM\Mapping\Column\IntColumn;
use Michel\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Michel\PaperORM\Mapping\Column\StringColumn;
use Michel\PaperORM\Metadata\ColumnMetadata;
use Michel\PaperORM\PaperConfiguration;
use Michel\UniTester\TestCase;
use Test\Michel\PaperORM\Helper\DataBaseHelperTest;

class PlatformTest extends TestCase
{

    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }

    protected function execute(): void
    {
        foreach (DataBaseHelperTest::drivers() as $params) {
            $em = EntityManager::createFromConfig(PaperConfiguration::fromArray($params));
            $this->testCreateTables($em);
            $this->testDropTable($em);
            $this->testDropColumn($em);
            $this->testAddColumn($em);
            $this->testRenameColumn($em);
            $em->getConnection()->close();
        }
    }

    public function testCreateTables(EntityManager $em)
    {
        $em->getConnection()->close();
        $em->getConnection()->connect();
        $platform = $em->getPlatform();
        $platform->createDatabaseIfNotExists();
        $platform->dropDatabase();
        $platform->createDatabaseIfNotExists();
        $platform->createTable('user', [
            new PrimaryKeyColumn('id'),
            new StringColumn('firstname'),
            new StringColumn('lastname'),
            new StringColumn('email'),
            new StringColumn('password'),
            new BoolColumn('is_active'),
        ]);


        $platform->createTable('post', [
            new PrimaryKeyColumn('id'),
            new IntColumn('user_id'),
            new StringColumn('title'),
            new StringColumn('content'),
        ]);

        $this->assertStrictEquals(2, count($platform->listTables()));
        $this->assertEquals(['user', 'post'], $platform->listTables());
    }


    public function testDropTable(EntityManager $em)
    {
        $em->getConnection()->close();
        $em->getConnection()->connect();
        $platform = $em->getPlatform();
        $platform->createDatabaseIfNotExists();
        $platform->dropDatabase();
        $platform->createDatabaseIfNotExists();

        $platform = $em->getPlatform();
        $platform->createTable('user', [
            new PrimaryKeyColumn('id'),
            new StringColumn('firstname'),
            new StringColumn('lastname'),
            new StringColumn('email'),
            new StringColumn('password'),
            new BoolColumn('is_active'),
        ]);

        $this->assertStrictEquals(1, count($platform->listTables()));
        $platform->dropTable('user');
        $this->assertStrictEquals(0, count($platform->listTables()));
    }

    public function testDropColumn(EntityManager $em)
    {
        $platform = $em->getPlatform();
        if ($platform->getSchema()->supportsDropColumn() === false) {
            return;
        }

        $em->getConnection()->close();
        $em->getConnection()->connect();
        $platform = $em->getPlatform();
        $platform->createDatabaseIfNotExists();
        $platform->dropDatabase();
        $platform->createDatabaseIfNotExists();

        $platform->createTable('user', [
            new PrimaryKeyColumn('id'),
            new StringColumn('firstname'),
            new StringColumn('lastname'),
            new StringColumn('email'),
            new StringColumn('password'),
            new BoolColumn('is_active'),
        ]);

        $this->assertStrictEquals(6, count($platform->listTableColumns('user')));
        $platform->dropColumn('user', new StringColumn('lastname'));
        $this->assertStrictEquals(5, count($platform->listTableColumns('user')));
    }

    public function testAddColumn(EntityManager $em)
    {
        $em->getConnection()->close();
        $em->getConnection()->connect();
        $platform = $em->getPlatform();
        $platform->createDatabaseIfNotExists();
        $platform->dropDatabase();
        $platform->createDatabaseIfNotExists();

        $platform = $em->getPlatform();
        $platform->createTable('user', [
            new PrimaryKeyColumn('id'),
            new StringColumn('firstname'),
            new StringColumn('lastname'),
            new StringColumn('email'),
            new StringColumn('password'),
            new BoolColumn('is_active'),
        ]);

        $this->assertStrictEquals(6, count($platform->listTableColumns('user')));
        $platform->addColumn('user', new StringColumn('username'));
        $this->assertStrictEquals(7, count($platform->listTableColumns('user')));
    }

    public function testRenameColumn(EntityManager $em)
    {
        $em->getConnection()->close();
        $em->getConnection()->connect();
        $platform = $em->getPlatform();
        $platform->createDatabaseIfNotExists();
        $platform->dropDatabase();
        $platform->createDatabaseIfNotExists();

        $platform = $em->getPlatform();
        $platform->createTable('user', [
            new PrimaryKeyColumn('id'),
            new StringColumn('firstname'),
            new StringColumn('lastname'),
            new StringColumn('email'),
            new StringColumn('password'),
            new BoolColumn('is_active'),
        ]);

        $columnsAsArray = array_map(function (ColumnMetadata $column) {
            return $column->toArray();
        }, $platform->listTableColumns('user'));
        $columns = array_column($columnsAsArray, 'name');
        $this->assertTrue(in_array('firstname', $columns));

        $platform->renameColumn('user', 'firstname', 'prenom');

        $columnsAsArray = array_map(function (ColumnMetadata $column) {
            return $column->toArray();
        }, $platform->listTableColumns('user'));
        $columns = array_column($columnsAsArray, 'name');
        $this->assertTrue(!in_array('firstname', $columns));
        $this->assertTrue(in_array('prenom', $columns));
    }

}
