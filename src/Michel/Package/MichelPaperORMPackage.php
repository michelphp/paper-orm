<?php

namespace Michel\PaperORM\Michel\Package;

use Michel\Michel\Package\PackageInterface;
use Michel\PaperORM\Collector\EntityDirCollector;
use Michel\PaperORM\Command\DatabaseCreateCommand;
use Michel\PaperORM\Command\DatabaseDropCommand;
use Michel\PaperORM\Command\DatabaseSyncCommand;
use Michel\PaperORM\Command\Migration\MigrationDiffCommand;
use Michel\PaperORM\Command\Migration\MigrationMigrateCommand;
use Michel\PaperORM\Command\QueryExecuteCommand;
use Michel\PaperORM\Command\ShowTablesCommand;
use Michel\PaperORM\EntityManager;
use Michel\PaperORM\EntityManagerInterface;
use Michel\PaperORM\Migration\PaperMigration;
use Michel\PaperORM\PaperConfiguration;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class MichelPaperORMPackage implements PackageInterface
{
    public function getDefinitions(): array
    {
        return [
            PaperConfiguration::class => static function (ContainerInterface $container) {
                $paperConf = PaperConfiguration::fromDsn(
                    $container->get('paper.orm.dsn'),
                    $container->get('paper.orm.debug')
                );
                $logger = $container->get('paper.orm.logger');
                if ($logger) {
                    $paperConf->withLogger($container->get('paper.orm.logger'));
                }
                return $paperConf;
            },
            EntityDirCollector::class => static function (ContainerInterface $container) {
                return EntityDirCollector::bootstrap([$container->get('paper.orm.entity_dir')]);
            },
            EntityManagerInterface::class => static function (ContainerInterface $container) {
                return $container->get(EntityManager::class);
            },
            EntityManager::class => static function (ContainerInterface $container) {
                return EntityManager::createFromConfig($container->get(PaperConfiguration::class));
            },
            PaperMigration::class => static function (ContainerInterface $container) {
                return PaperMigration::create(
                    $container->get(EntityManagerInterface::class),
                    $container->get('paper.orm.migrations_table'),
                    $container->get('paper.orm.migrations_dir')
                );
            },
            MigrationDiffCommand::class => static function (ContainerInterface $container) {
                return new MigrationDiffCommand($container->get(PaperMigration::class), $container->get(EntityDirCollector::class));
            },
            DatabaseDropCommand::class => static function (ContainerInterface $container) {
                return new DatabaseDropCommand($container->get(EntityManagerInterface::class), $container->get('michel.environment'));
            },
            DatabaseSyncCommand::class => static function (ContainerInterface $container) {
                return new DatabaseSyncCommand($container->get(PaperMigration::class), $container->get(EntityDirCollector::class), $container->get('michel.environment'));
            }
        ];
    }

    public function getParameters(): array
    {
        return [
            'paper.orm.dsn' => getenv('DATABASE_URL') ?? '',
            'paper.orm.debug' => static function (ContainerInterface $container) {
                return $container->get('michel.debug');
            },
            'paper.orm.logger' => static function (ContainerInterface $container) {
                if ($container->has(LoggerInterface::class)) {
                    return  $container->get(LoggerInterface::class);
                }
                return null;
            },
            'paper.orm.entity_dir' => getenv('PAPER_ORM_ENTITY_DIR') ?: static function (ContainerInterface $container) {
                $folder = $container->get('michel.project_dir') . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Entity';
                if (!is_dir($folder)) {
                    mkdir($folder, 0777, true);
                }
                return $folder;
            },
            'paper.orm.migrations_dir' => getenv('PAPER_ORM_MIGRATIONS_DIR') ?: static function (ContainerInterface $container) {
                $folder = $container->get('michel.project_dir') . DIRECTORY_SEPARATOR . 'migrations';
                if (!is_dir($folder)) {
                    mkdir($folder, 0777, true);
                }
                return $folder;
            },
            'paper.orm.migrations_table' => getenv('PAPER_ORM_MIGRATIONS_TABLE') ?: 'paper_mig_version',
        ];
    }

    public function getRoutes(): array
    {
        return [];
    }

    public function getControllerSources(): array
    {
        return [];
    }

    public function getListeners(): array
    {
        return [];
    }

    public function getCommandSources(): array
    {
        return [
            DatabaseCreateCommand::class,
            DatabaseDropCommand::class,
            DatabaseSyncCommand::class,
            MigrationDiffCommand::class,
            MigrationMigrateCommand::class,
            QueryExecuteCommand::class,
            ShowTablesCommand::class,
        ];
    }
}
