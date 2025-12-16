<?php

namespace Test\Michel\PaperORM;

use Michel\Console\CommandParser;
use Michel\Console\CommandRunner;
use Michel\Console\Output;
use Michel\PaperORM\Collector\EntityDirCollector;
use Michel\PaperORM\Command\DatabaseSyncCommand;
use Michel\PaperORM\Command\ShowTablesCommand;
use Michel\PaperORM\EntityManager;
use Michel\PaperORM\Mapping\Column\BoolColumn;
use Michel\PaperORM\Mapping\Column\IntColumn;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Michel\PaperORM\Mapping\Column\StringColumn;
use Michel\PaperORM\Migration\PaperMigration;
use Michel\PaperORM\PaperConfiguration;
use Michel\UniTester\TestCase;
use Test\Michel\PaperORM\Entity\UserTest;
use Test\Michel\PaperORM\Helper\DataBaseHelperTest;

class DatabaseSyncCommandTest extends TestCase
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

        $paperMigration = PaperMigration::create($em, 'mig_versions', __DIR__ . '/migrations');
        $runner = new CommandRunner([
            new DatabaseSyncCommand($paperMigration, EntityDirCollector::bootstrap([__DIR__ . '/Entity']), 'test'),
        ]);

        $out = [];
        $code = $runner->run(new CommandParser(['', 'paper:database:sync', '--no-execute']), new Output(function ($message) use(&$out) {
            $out[] = $message;
        }));
        $this->assertEquals(0, $code);
        $this->assertStringContains( implode(' ', $out), "[INFO] Preview mode only — SQL statements were displayed but NOT executed.");

        $out = [];
        $code = $runner->run(new CommandParser(['', 'paper:database:sync']), new Output(function ($message) use(&$out) {
            $out[] = $message;
        }));

        $this->assertEquals(0, $code);
        $this->assertStringContains( implode(' ', $out), "✔ Executed:");
        $out = [];
        $code = $runner->run(new CommandParser(['', 'paper:database:sync']), new Output(function ($message) use(&$out) {
            $out[] = $message;
        }));


        $this->assertEquals(0, $code);
        $this->assertStringContains( implode(' ', $out), "No differences detected — all entities are already in sync with the database schema.");

        $platform->dropDatabase();
    }
}
