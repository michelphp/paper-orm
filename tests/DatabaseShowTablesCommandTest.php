<?php

namespace Test\Michel\PaperORM;

use Michel\Console\CommandParser;
use Michel\Console\CommandRunner;
use Michel\Console\Output;
use Michel\PaperORM\Command\ShowTablesCommand;
use Michel\PaperORM\EntityManager;
use Michel\PaperORM\Mapping\Column\BoolColumn;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Michel\PaperORM\Mapping\Column\StringColumn;
use Michel\PaperORM\PaperConfiguration;
use Michel\UniTester\TestCase;
use Test\Michel\PaperORM\Entity\UserTest;
use Test\Michel\PaperORM\Helper\DataBaseHelperTest;

class DatabaseShowTablesCommandTest extends TestCase
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
            $this->executeTest($em);
            $em->getConnection()->close();
        }
    }

    private function executeTest(EntityManager $em)
    {
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
            new JoinColumn('user_id', UserTest::class),
            new StringColumn('title'),
            new StringColumn('content'),
        ]);

        $runner = new CommandRunner([
            new ShowTablesCommand($em)
        ]);

        $out = [];
        $code = $runner->run(new CommandParser(['', 'paper:show:tables', '--columns']), new Output(function ($message) use(&$out) {
            $out[] = $message;
        }));

        $this->assertEquals(0, $code);
        $this->assertEquals(131, count($out));

        $out = [];
        $code = $runner->run(new CommandParser(['', 'paper:show:tables', 'post']), new Output(function ($message) use(&$out) {
            $out[] = $message;
        }));

        $this->assertEquals(0, $code);
        $this->assertEquals(15, count($out));

        $out = [];
        $code = $runner->run(new CommandParser(['', 'paper:show:tables', 'post', '--columns']), new Output(function ($message) use(&$out) {
            $out[] = $message;
        }));

        $this->assertEquals(0, $code);
        $this->assertEquals(61, count($out));

        $platform->dropDatabase();
    }
}
