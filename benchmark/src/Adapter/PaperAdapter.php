<?php

declare(strict_types=1);

namespace Bench\Adapter;

use Bench\Entity\Paper\User;
use Bench\Schema;
use Michel\PaperORM\EntityManager;
use Michel\PaperORM\PaperConfiguration;
use Michel\PaperORM\Repository\Repository;

final class PaperAdapter implements OrmAdapter
{
    private ?EntityManager $em = null;

    public function label(): string
    {
        return 'PaperORM';
    }

    public function version(): string
    {
        return Adapters::packageVersion('michel/paper-orm');
    }

    public function boot(): void
    {
        $this->em = EntityManager::createFromConfig(PaperConfiguration::fromArray([
            'driver' => 'sqlite',
            'user' => null,
            'password' => null,
            'memory' => true,
        ]));
    }

    public function pdo(): \PDO
    {
        return $this->em->getConnection()->getPdo();
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
        return $this->users()->find($id)->toObject();
    }

    public function findAll(): array
    {
        return $this->users()->findBy()->toObject();
    }

    public function findAllAsArray(): array
    {
        return $this->users()->findBy()->toArray();
    }

    public function findAllWithPosts(): array
    {
        return $this->users()->findBy()->with('posts')->toObject();
    }

    public function countPosts(object $user): int
    {
        return count($user->getPosts());
    }

    public function paginate(int $offset, int $limit): array
    {
        return $this->users()->findBy()->orderBy('id')->offset($offset)->limit($limit)->toObject();
    }

    public function paginateWithPosts(int $offset, int $limit): array
    {
        return $this->users()->findBy()->with('posts')->orderBy('id')->offset($offset)->limit($limit)->toObject();
    }

    public function saveAll(array $users): void
    {
        foreach ($users as $user) {
            $this->em->persist($user);
        }
        $this->em->flush();
    }

    public function removeAll(array $users): void
    {
        foreach ($users as $user) {
            $this->em->remove($user);
        }
        $this->em->flush();
    }

    private function users(): Repository
    {
        return $this->em->getRepository(User::class);
    }
}
