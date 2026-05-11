<?php

namespace Test\Michel\PaperORM;

use Michel\PaperORM\Driver\MariaDBDriver;
use Michel\PaperORM\Driver\SqliteDriver;
use Michel\PaperORM\EntityManager;
use Michel\PaperORM\Mapper\ColumnMapper;
use Michel\PaperORM\Mapping\Column\IntColumn;
use Michel\PaperORM\Mapping\Column\StringColumn;
use Michel\PaperORM\Migration\PaperMigration;
use Michel\PaperORM\PaperConfiguration;
use Michel\UniTester\TestCase;
use RuntimeException;
use Test\Michel\PaperORM\Entity\PostTest;
use Test\Michel\PaperORM\Entity\UserTest;
use Test\Michel\PaperORM\Helper\DataBaseHelperTest;

class MigrationTest extends TestCase
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
        foreach (DataBaseHelperTest::drivers() as $params) {
            $em = EntityManager::createFromConfig(PaperConfiguration::fromArray($params));
            $paperMigration = PaperMigration::create($em, 'mig_versions', $this->migrationDir);
            $platform = $em->getPlatform();
            $platform->createDatabaseIfNotExists();
            $platform->dropDatabase();
            $platform->createDatabaseIfNotExists();
            $this->testDiff($paperMigration);
            $this->testExecute($paperMigration);
            $this->testColumnModification($paperMigration);
            $this->testFailedMigration($paperMigration);
            $em->getConnection()->close();
            $this->tearDown();
        }
    }


    private function testDiff(PaperMigration $paperMigration): void
    {
        $em = $paperMigration->getEntityManager();
        $driver = $em->getConnection()->getDriver();
        $em->getConnection()->close();
        $migrationFile = $paperMigration->generateMigrationFromEntities([
            UserTest::class,
            PostTest::class
        ]);

        switch (get_class($driver)) {
            case SqliteDriver::class:
                $lines = file($migrationFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                $this->assertEquals($lines, array (
                    0 => '-- UP MIGRATION --',
                    1 => 'CREATE TABLE `user` (`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,`firstname` VARCHAR(255) NOT NULL,`lastname` VARCHAR(255) NOT NULL,`email` VARCHAR(255) NOT NULL,`password` VARCHAR(255) NOT NULL,`token` VARCHAR(32) NOT NULL,`is_active` BOOLEAN NOT NULL,`created_at` DATETIME,`last_post_id` INTEGER,FOREIGN KEY (`last_post_id`) REFERENCES `post` (id) ON DELETE SET NULL ON UPDATE NO ACTION);',
                    2 => 'CREATE UNIQUE INDEX UNIQ_439C366749B149E ON `user` (`token`);',
                    3 => 'CREATE UNIQUE INDEX UNIQ_D1AF9A5028469E1 ON `user` (`last_post_id`);',
                    4 => 'CREATE TABLE `post` (`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,`title` VARCHAR(255) NOT NULL,`content` VARCHAR(255) NOT NULL,`slug` VARCHAR(128) NOT NULL,`created_at` DATETIME NOT NULL,`user_id` INTEGER,FOREIGN KEY (`user_id`) REFERENCES `user` (id) ON DELETE SET NULL ON UPDATE NO ACTION);',
                    5 => 'CREATE UNIQUE INDEX UNIQ_07FCECC126F6AA3 ON `post` (`slug`);',
                    6 => 'CREATE INDEX IX_3EF7B93DDDDEB8CFA ON `post` (`user_id`);',
                    7 => '-- DOWN MIGRATION --',
                    8 => 'DROP INDEX UNIQ_439C366749B149E;',
                    9 => 'DROP INDEX UNIQ_D1AF9A5028469E1;',
                    10 => 'DROP TABLE `user`;',
                    11 => 'DROP INDEX UNIQ_07FCECC126F6AA3;',
                    12 => 'DROP INDEX IX_3EF7B93DDDDEB8CFA;',
                    13 => 'DROP TABLE `post`;',
                ));
                break;
            case MariaDBDriver::class:
                $lines = file($migrationFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $this->assertEquals($lines, array (
                    0 => '-- UP MIGRATION --',
                    1 => 'CREATE TABLE `user` (`id` INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,`firstname` VARCHAR(255) NOT NULL,`lastname` VARCHAR(255) NOT NULL,`email` VARCHAR(255) NOT NULL,`password` VARCHAR(255) NOT NULL,`token` VARCHAR(32) NOT NULL,`is_active` TINYINT(1) NOT NULL,`created_at` DATETIME DEFAULT NULL,`last_post_id` INT(11) DEFAULT NULL);',
                    2 => 'CREATE UNIQUE INDEX UNIQ_439C366749B149E ON `user` (`token`);',
                    3 => 'CREATE UNIQUE INDEX UNIQ_D1AF9A5028469E1 ON `user` (`last_post_id`);',
                    4 => 'CREATE TABLE `post` (`id` INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,`title` VARCHAR(255) NOT NULL,`content` VARCHAR(255) NOT NULL,`slug` VARCHAR(128) NOT NULL,`created_at` DATETIME NOT NULL,`user_id` INT(11) DEFAULT NULL);',
                    5 => 'CREATE UNIQUE INDEX UNIQ_07FCECC126F6AA3 ON `post` (`slug`);',
                    6 => 'CREATE INDEX IX_3EF7B93DDDDEB8CFA ON `post` (`user_id`);',
                    7 => 'ALTER TABLE `user` ADD CONSTRAINT FK_8D93D6492D053F64 FOREIGN KEY (`last_post_id`) REFERENCES `post`(`id`) ON DELETE SET NULL ON UPDATE NO ACTION;',
                    8 => 'ALTER TABLE `post` ADD CONSTRAINT FK_5A8A6C8DA76ED395 FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL ON UPDATE NO ACTION;',
                    9 => '-- DOWN MIGRATION --',
                    10 => 'ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6492D053F64;',
                    11 => 'ALTER TABLE `post` DROP FOREIGN KEY FK_5A8A6C8DA76ED395;',
                    12 => 'DROP INDEX UNIQ_439C366749B149E ON `user`;',
                    13 => 'DROP INDEX UNIQ_D1AF9A5028469E1 ON `user`;',
                    14 => 'DROP TABLE `user`;',
                    15 => 'DROP INDEX UNIQ_07FCECC126F6AA3 ON `post`;',
                    16 => 'DROP INDEX IX_3EF7B93DDDDEB8CFA ON `post`;',
                    17 => 'DROP TABLE `post`;',
                ));
                break;
            default:
                throw new RuntimeException(sprintf('Driver %s not supported', get_class($driver)));
        }

    }

    private function testExecute(PaperMigration $paperMigration): void
    {
        $paperMigration->migrate();
        $successList = $paperMigration->getSuccessList();
        $this->assertTrue(count($successList) === 1);

        $migrationFile = $paperMigration->generateMigrationFromEntities([UserTest::class]);
        $this->assertNull($migrationFile);
    }

    private function testColumnModification(PaperMigration $paperMigration): void
    {
        $em = $paperMigration->getEntityManager();
        $driver = $em->getConnection()->getDriver();

        $userColumns = ColumnMapper::getColumns(UserTest::class);
        $userColumns[3] = (new StringColumn(null, 255, true, null, true))->bindProperty('email');
        $userColumns[] = (new IntColumn(null, false, 0))->bindProperty('childs');
        $migrationFile = $paperMigration->generateMigrationFromTables([
            'user' => [
                'columns' => $userColumns,
                'indexes' => []
            ]
        ]);
        $paperMigration->migrate();
        $successList = $paperMigration->getSuccessList();
        $this->assertTrue(count($successList) === 1);
        $this->assertEquals(pathinfo($migrationFile, PATHINFO_FILENAME), $successList[0]);
        switch (get_class($driver)) {
            case SqliteDriver::class:
                $schema = $driver->createDatabaseSchema();
                $lines = file($migrationFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($schema->supportsDropColumn()) {
                    $this->assertEquals($lines, array());
                } else {
                    $this->assertEquals($lines, array (
                        0 => '-- UP MIGRATION --',
                        1 => 'ALTER TABLE `user` ADD `childs` INTEGER NOT NULL DEFAULT 0;',
                        2 => 'CREATE UNIQUE INDEX UNIQ_27C6B8E780CFC29 ON `user` (`email`);',
                        3 => '-- Modify column email is not supported with Michel\\PaperORM\\Schema\\SqliteSchema. Consider creating a new column and migrating the data.;',
                        4 => '-- DOWN MIGRATION --',
                        5 => '-- Drop column childs is not supported with Michel\\PaperORM\\Schema\\SqliteSchema. You might need to manually drop the column.;',
                        6 => 'DROP INDEX UNIQ_27C6B8E780CFC29;',
                    ));
                }
                break;
            case MariaDBDriver::class:
                $lines = file($migrationFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $this->assertEquals($lines, array (
                    0 => '-- UP MIGRATION --',
                    1 => 'ALTER TABLE `user` ADD COLUMN `childs` INT(11) NOT NULL DEFAULT 0;',
                    2 => 'CREATE UNIQUE INDEX UNIQ_27C6B8E780CFC29 ON `user` (`email`);',
                    3 => 'ALTER TABLE `user` MODIFY COLUMN `email` VARCHAR(255) DEFAULT NULL;',
                    4 => '-- DOWN MIGRATION --',
                    5 => 'ALTER TABLE `user` DROP COLUMN `childs`;',
                    6 => 'DROP INDEX UNIQ_27C6B8E780CFC29 ON `user`;',
                    7 => 'ALTER TABLE `user` MODIFY COLUMN `email` VARCHAR(255) NOT NULL;',
                ));
                break;
            default:
                throw new RuntimeException(sprintf('Driver %s not supported', get_class($driver)));
        }

    }

    private function testFailedMigration(PaperMigration $paperMigration): void
    {
        $paperMigration->generateMigration();

        $this->expectException(RuntimeException::class, function () use ($paperMigration) {
            $paperMigration->migrate();
        });
        $successList = $paperMigration->getSuccessList();
        $this->assertTrue(count($successList) === 0);

    }
}
