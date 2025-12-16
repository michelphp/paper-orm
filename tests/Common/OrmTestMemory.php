<?php

namespace Test\Michel\PaperORM\Common;

use Michel\PaperORM\EntityManager;
use Michel\PaperORM\PaperConfiguration;
use Michel\UniTester\TestCase;
use Test\Michel\PaperORM\Entity\UserTest;
use Test\Michel\PaperORM\Helper\DataBaseHelperTest;

class OrmTestMemory extends TestCase
{

    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }

    protected function execute(): void
    {
        foreach (DataBaseHelperTest::drivers() as  $params) {
            $em = EntityManager::createFromConfig(PaperConfiguration::fromArray($params));
            DataBaseHelperTest::init($em, 5000, false);
            $memory = memory_get_usage();
            $users = $em->getRepository(UserTest::class)
                ->findBy()
                ->toObject();
            $this->assertStrictEquals(5000, count($users));
            foreach ($users as $user) {
                $this->assertInstanceOf(UserTest::class, $user);
                $this->assertNotEmpty($user);
            }
            $memory = memory_get_usage(true) - $memory;
            $memory = ceil($memory / 1024 / 1024);
            $this->assertTrue( $memory <= 30 );
            $em->getConnection()->close();
        }
    }
}
