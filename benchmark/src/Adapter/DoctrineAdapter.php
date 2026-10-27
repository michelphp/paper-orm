<?php

declare(strict_types=1);

namespace Bench\Adapter;

use Bench\Entity\Doctrine\User;
use Bench\Schema;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class DoctrineAdapter implements OrmAdapter
{
    private ?EntityManager $em = null;

    /**
     * Shared by every entity manager of the process, like PaperORM's static metadata caches
     * (and like a persistent cache in production).
     */
    private static ?ArrayAdapter $cache = null;

    public function label(): string
    {
        return 'Doctrine';
    }

    public function version(): string
    {
        return Adapters::packageVersion('doctrine/orm');
    }

    public function boot(): void
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl(new AttributeDriver([dirname(__DIR__) . '/Entity/Doctrine']));

        self::$cache = self::$cache ?? new ArrayAdapter();
        $config->setMetadataCache(self::$cache);
        $config->setQueryCache(self::$cache);

        if (PHP_VERSION_ID >= 80400 && method_exists($config, 'enableNativeLazyObjects')) {
            $config->enableNativeLazyObjects(true);
        } else {
            $config->setProxyDir(dirname(__DIR__, 2) . '/var/doctrine-proxies');
            $config->setProxyNamespace('BenchDoctrineProxies');
            $config->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);
        }

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);
        $this->em = new EntityManager($connection, $config);
    }

    public function pdo(): \PDO
    {
        $pdo = $this->em->getConnection()->getNativeConnection();
        if (!$pdo instanceof \PDO) {
            throw new \LogicException('Doctrine native connection is not a PDO instance.');
        }

        return $pdo;
    }

    public function warmUp(): void
    {
        $this->findAllWithPosts();
        $this->clear();
    }

    public function clear(): void
    {
        $this->em->clear();
    }

    public function insertUsers(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $user = (new User())
                ->setName('User ' . $i)
                ->setEmail('user' . $i . '@example.com')
                ->setAge(Schema::age($i));
            $this->em->persist($user);
        }
        $this->em->flush();
    }

    public function findById(int $id): ?object
    {
        return $this->em->find(User::class, $id);
    }

    public function findAll(): array
    {
        return $this->em->getRepository(User::class)->findAll();
    }

    public function findAllAsArray(): array
    {
        return $this->em->createQuery(sprintf('SELECT u FROM %s u', User::class))->getArrayResult();
    }

    public function findAllWithPosts(): array
    {
        return $this->em->createQuery(sprintf('SELECT u, p FROM %s u LEFT JOIN u.posts p', User::class))->getResult();
    }

    public function countPosts(object $user): int
    {
        return $user->getPosts()->count();
    }

    public function paginate(int $offset, int $limit): array
    {
        return $this->em->createQuery(sprintf('SELECT u FROM %s u ORDER BY u.id ASC', User::class))
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getResult();
    }

    public function paginateWithPosts(int $offset, int $limit): array
    {
        // A plain setMaxResults() on a fetch-joined collection limits SQL rows, the Paginator is required.
        $query = $this->em->createQuery(sprintf('SELECT u, p FROM %s u LEFT JOIN u.posts p ORDER BY u.id ASC', User::class))
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return iterator_to_array(new Paginator($query, true), false);
    }

    public function saveAll(array $users): void
    {
        // Doctrine tracks changes on managed entities, persist() is not needed.
        $this->em->flush();
    }

    public function removeAll(array $users): void
    {
        foreach ($users as $user) {
            $this->em->remove($user);
        }
        $this->em->flush();
    }
}
