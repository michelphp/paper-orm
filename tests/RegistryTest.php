<?php

namespace Test\Michel\PaperORM;

use Michel\Lock\Handler\FlockHandler;
use Michel\Lock\LockHandlerInterface;
use Michel\Lock\Locker;
use Michel\PaperORM\Collector\EntityDirCollector;
use Michel\PaperORM\EntityManager;
use Michel\PaperORM\Expression\Expr;
use Michel\PaperORM\Manager\PaperSequenceManager;
use Michel\PaperORM\Migration\PaperMigration;
use Michel\PaperORM\PaperConfiguration;
use Michel\PaperORM\Proxy\ProxyInterface;
use Michel\PaperORM\Tools\EntityExplorer;
use Michel\UniTester\TestCase;
use Test\Michel\PaperORM\Entity\PostTest;
use Test\Michel\PaperORM\Entity\UserTest;
use Test\Michel\PaperORM\Helper\DataBaseHelperTest;

class RegistryTest extends TestCase
{
    private string $migrationDir;

    protected function setUp(): void
    {
        $this->migrationDir = __DIR__ . '/migrations';
        $this->tearDown();
    }

    protected function tearDown(): void
    {
        $folder = $this->migrationDir;
        array_map('unlink', glob("$folder/*.*"));
    }

    protected function execute(): void
    {
        foreach (DataBaseHelperTest::drivers() as  $params) {
            $em = EntityManager::createFromConfig(PaperConfiguration::fromArray($params));
            $paperMigration = PaperMigration::create($em, 'mig_versions', $this->migrationDir);
            $entityDirCollector = EntityDirCollector::bootstrap();
            $this->assertEquals(1, count($entityDirCollector->all()));
            $entities = EntityExplorer::getEntities($entityDirCollector->all());
            $this->assertEquals(1, count($entities['system']));
            $result = $paperMigration->getSqlDiffFromEntities($entities['system']);
            foreach ($result as $sql) {
                $em->getConnection()->executeStatement($sql);
            }
            $this->test($em);
            $em->getConnection()->close();
        }
    }

    private function test(EntityManager $em): void
    {
        $em->registry()->set('test', 'test');
        $this->assertEquals('test', $em->registry()->get('test'));

        $em->registry()->remove('test');
        $this->assertEquals(null, $em->registry()->get('test'));

        $value = $em->sequence()->peek("test");
        $this->assertEquals(1, $value);

        $em->sequence()->increment("test");
        $value = $em->sequence()->peek("test");
        $this->assertEquals(2, $value);

        $this->testSequenceLock($em);
    }

    private function testSequenceLock(EntityManager $em): void
    {
        $handler = new class implements LockHandlerInterface {
            public array $calls = [];

            public function lock(string $key, bool $wait = false): bool
            {
                $this->calls[] = $wait ? 'lock (wait)' : 'lock';
                return true;
            }

            public function unlock(string $key): void
            {
                $this->calls[] = 'unlock';
            }
        };
        $sequence = new PaperSequenceManager($em->registry(), new Locker($handler));

        // Locked once, released by increment().
        $sequence->lock('test');
        $sequence->lock('TEST');
        $sequence->peek('test');
        $sequence->increment('test');
        $this->assertEquals(['lock (wait)', 'unlock'], $handler->calls);

        // Not locked by the caller: increment() locks for its own read and write.
        $sequence->increment('test');
        $this->assertEquals(['lock (wait)', 'unlock', 'lock (wait)', 'unlock'], $handler->calls);

        // unlockAll() releases what an insert left locked, once.
        $sequence->lock('test');
        $sequence->unlockAll();
        $sequence->unlockAll();
        $this->assertEquals(['lock (wait)', 'unlock', 'lock (wait)', 'unlock', 'lock (wait)', 'unlock'], $handler->calls);
        $this->assertEquals(4, $sequence->peek('test'));

        // With the real file lock, another handle cannot take a locked sequence.
        $other = new FlockHandler();
        $lockKey = 'paper_sequence.' . md5('test');
        $em->sequence()->lock('test');
        $this->assertFalse($other->lock($lockKey));
        $em->sequence()->unlockAll();
        $this->assertTrue($other->lock($lockKey));
        $other->unlock($lockKey);
    }

}
