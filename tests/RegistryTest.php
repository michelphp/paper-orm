<?php

namespace Test\Michel\PaperORM;

use Michel\PaperORM\Collector\EntityDirCollector;
use Michel\PaperORM\EntityManager;
use Michel\PaperORM\Expression\Expr;
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
    }

}
