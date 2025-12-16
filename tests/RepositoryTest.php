<?php

namespace Test\Michel\PaperORM;

use Michel\PaperORM\EntityManager;
use Michel\PaperORM\PaperConfiguration;
use Michel\PaperORM\Proxy\ProxyInterface;
use Michel\UniTester\TestCase;
use Test\Michel\PaperORM\Entity\PostTest;
use Test\Michel\PaperORM\Entity\UserTest;
use Test\Michel\PaperORM\Helper\DataBaseHelperTest;

class RepositoryTest extends TestCase
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
            DataBaseHelperTest::init($em);
            $this->testSelectWithoutJoin($em);
            $this->testSelectWithoutProxy($em);
            $this->testSelectInnerJoin($em);
            $this->testSelectLeftJoin($em);
            $em->getConnection()->close();
        }
    }

    public function testSelectWithoutJoin(EntityManager  $em): void
    {
        $userRepository = $em->getRepository(UserTest::class);
        $user = $userRepository->findBy()
            ->first()->orderBy('id')->toArray();

        $this->assertStrictEquals( 1, $user['id'] );
        $this->assertStrictEquals( 'John0', $user['firstname'] );
        $this->assertStrictEquals( 'Doe0', $user['lastname'] );
        $this->assertStrictEquals( '0bqQpB@example.com', $user['email'] );
        $this->assertStrictEquals( 'password123', $user['password'] );

        $user = $userRepository->find(1)->toArray();

        $this->assertStrictEquals( 1, $user['id'] );
        $this->assertStrictEquals( 'John0', $user['firstname'] );
        $this->assertStrictEquals( 'Doe0', $user['lastname'] );
        $this->assertStrictEquals( '0bqQpB@example.com', $user['email'] );
        $this->assertStrictEquals( 'password123', $user['password'] );

        /**
         * @var UserTest $user
         */
        $user = $userRepository->find(1)->toObject();

        $this->assertStrictEquals( 1, $user->getId() );
        $this->assertStrictEquals( 'John0', $user->getFirstname() );
        $this->assertStrictEquals( 'Doe0', $user->getLastname() );
        $this->assertStrictEquals( '0bqQpB@example.com', $user->getEmail() );
        $this->assertStrictEquals( 'password123', $user->getPassword() );
        $this->assertInstanceOf( \DateTimeInterface::class, $user->getCreatedAt() );
        $this->assertEmpty($user->getPosts()->toArray());
        $this->assertNull($user->getLastPost());

        $users = $userRepository->findBy()->orderBy('id')->toArray();
        $this->assertStrictEquals( 1, $users[0]['id'] );
        $this->assertStrictEquals(5, count($users));

        $users = $userRepository->findBy()->orderBy('id')->toObject();
        $this->assertStrictEquals(5, count($users));
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
        }
    }

    public function testSelectWithoutProxy(EntityManager $em): void
    {
        $userRepository = $em->getRepository(UserTest::class);
        $users = $userRepository->findBy()
            ->with(PostTest::class)
            ->orderBy('id', 'DESC')
            ->toReadOnlyObject();


        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            $this->assertTrue(!$user instanceof ProxyInterface);
            $this->assertTrue(!$user->getLastPost() instanceof ProxyInterface);
            foreach ($user->getPosts() as $post) {
                $this->assertTrue(!$post instanceof ProxyInterface);
            }
        }

        $users = $userRepository->findBy()
            ->with(PostTest::class)
            ->orderBy('id', 'DESC')
            ->toObject();

        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            $this->assertInstanceOf(ProxyInterface::class, $user);
            if ($user->getLastPost()) {
                $this->assertInstanceOf(ProxyInterface::class, $user->getLastPost());
            }
            foreach ($user->getPosts() as $post) {
                $this->assertInstanceOf(ProxyInterface::class, $post);
            }
        }
    }

    public function testSelectInnerJoin(EntityManager $em): void
    {
        $userRepository = $em->getRepository(UserTest::class);
        $user = $userRepository->findBy()
            ->first()
            ->orderBy('id', 'DESC')
            ->has(PostTest::class)
            ->toArray();

        $this->assertStrictEquals( 4, $user['id'] );
        $this->assertTrue(is_array( $user['posts'] ));
        $this->assertNotEmpty($user['posts']);
        $this->assertTrue(is_array( $user['lastPost'] ));
        $this->assertNotEmpty($user['lastPost']);

        $em->clear();
        /**
         * @var UserTest $user
         */
        $user = $userRepository->findBy()
            ->first()
            ->orderBy('id', 'DESC')
            ->has(PostTest::class)
            ->toObject();

        $this->assertStrictEquals( 4, $user->getId() );
        $this->assertNotEmpty($user->getPosts()->toArray());
        $this->assertInstanceOf(PostTest::class, $user->getLastPost());


        $em->clear();
        $users = $userRepository->findBy()->orderBy('id', 'DESC')->has(PostTest::class)->toArray();
        $this->assertStrictEquals( 4, $users[0]['id'] );
        $this->assertStrictEquals(4, count($users));

        $em->clear();
        $users = $userRepository->findBy()->orderBy('id', 'DESC')->has(PostTest::class)->toObject();
        $this->assertStrictEquals( 4, $users[0]->getId() );
        $this->assertStrictEquals(4, count($users));
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            $this->assertInstanceOf(PostTest::class, $user->getLastPost());
            $this->assertEmpty($user->getLastPost()->getTags()->toArray());

            $this->assertNotEmpty($user->getPosts()->toArray());
            foreach ($user->getPosts() as $post) {
                $this->assertInstanceOf(PostTest::class, $post);
                $this->assertNull($post->getUser());
                $this->assertEmpty($post->getTags()->toArray());
            }
        }

        $em->clear();
        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->has('posts.tags')
            ->toObject();
        foreach ($users as $user) {;
            $this->assertInstanceOf(UserTest::class, $user);
            $this->assertNull($user->getLastPost());
            $this->assertNotEmpty($user->getPosts()->toArray());
            foreach ($user->getPosts() as $post) {
                $this->assertInstanceOf(PostTest::class, $post);
                $this->assertNull($post->getUser());
                $this->assertNotEmpty($post->getTags()->toArray());
            }
        }

        $em->clear();
        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->has('posts.tags')
            ->has('lastPost.tags')
            ->toObject();
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            $this->assertInstanceOf(PostTest::class, $user->getLastPost());
            $this->assertNotEmpty($user->getPosts()->toArray());
            $this->assertNotEmpty($user->getLastPost()->getTags()->toArray());
            foreach ($user->getPosts() as $post) {
                $this->assertInstanceOf(PostTest::class, $post);
                $this->assertNull($post->getUser());
                $this->assertNotEmpty($post->getTags()->toArray());
            }
        }

        $em->clear();
        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->has('posts.tags')
            ->has('posts.user')
            ->has('lastPost.tags')
            ->toObject();
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            $this->assertInstanceOf(PostTest::class, $user->getLastPost());
            $this->assertNotEmpty($user->getPosts()->toArray());
            $this->assertNotEmpty($user->getLastPost()->getTags()->toArray());

            $this->assertStrictEquals($user, $user->getLastPost()->getUser());
            foreach ($user->getPosts() as $post) {
                $this->assertStrictEquals($user, $post->getUser());
                $this->assertInstanceOf(PostTest::class, $post);
                $this->assertInstanceOf(UserTest::class, $post->getUser());
                $this->assertNotEmpty($post->getTags()->toArray());
            }
        }

        $em->clear();


        $countUsers = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->has('posts.tags')
            ->has('posts.comments')
            ->toCount();
        $this->assertStrictEquals(4, $countUsers);


        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->has('posts.tags')
            ->has('posts.comments')
            ->limit(2)
            ->toArray();

        $this->assertEquals(4, $users[0]['id']);
        $this->assertEquals(1, count($users[0]['posts']));
        $this->assertEquals(3, $users[1]['id']);
        $this->assertEquals(2, count($users[1]['posts']));

        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->has('posts.tags')
            ->has('posts.comments')
            ->offset(2)
            ->limit(2)
            ->toArray();

        $this->assertEquals(2, $users[0]['id']);
        $this->assertEquals(2, count($users[0]['posts']));
        $this->assertEquals(1, $users[1]['id']);
        $this->assertEquals(2, count($users[1]['posts']));

        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->has('posts.tags')
            ->has('posts.comments')
            ->toArray();


        $this->assertStrictEquals(4, count($users));
        foreach ($users as $user) {
            $this->assertTrue(is_array( $user['posts'] ));
            $this->assertNotEmpty($user['posts']);
            foreach ($user['posts'] as $post) {
                $this->assertTrue(!array_key_exists('user', $post));
                $this->assertNotEmpty($post['comments']);
                $this->assertNotEmpty($post['tags']);
                $this->assertStrictEquals(2, count($post['comments']));
            }
        };

        $em->clear();
        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->has('posts.tags')
            ->has('posts.comments')
            ->toObject();

        $this->assertStrictEquals(4, count($users));
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            $this->assertNull($user->getLastPost());
            $this->assertNotEmpty($user->getPosts()->toArray());
            foreach ($user->getPosts() as $post) {
                $this->assertInstanceOf(PostTest::class, $post);
                $this->assertNull($post->getUser());
                $this->assertNotEmpty($post->getTags()->toArray());
                $this->assertNotEmpty($post->getComments()->toArray());
            }
        };
        $em->clear();
        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->has('lastPost.comments')
            ->toObject();


        $this->assertStrictEquals(3, count($users));
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            $this->assertEmpty($user->getPosts()->toArray());

            $post = $user->getLastPost();
            $this->assertInstanceOf(PostTest::class, $post);
            $this->assertNull($post->getUser());
            $this->assertEmpty($post->getTags()->toArray());
            $this->assertNotEmpty($post->getComments()->toArray());
        }

        $users = $userRepository->findBy()
            ->has('lastPost.user')
            ->limit(2)
            ->toObject();

        $this->assertStrictEquals(2, count($users));
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            $this->assertInstanceOf(PostTest::class, $user->getLastPost());
            $this->assertStrictEquals($user, $user->getLastPost()->getUser());
            $this->assertEmpty($user->getPosts()->toArray());
        }
    }

    public function testSelectLeftJoin(EntityManager $em): void
    {
        $userRepository = $em->getRepository(UserTest::class);
        $user = $userRepository->findBy()
            ->first()
            ->orderBy('id', 'DESC')
            ->with(PostTest::class)
            ->toArray();


        $this->assertStrictEquals( 5, $user['id'] );
        $this->assertTrue(is_array( $user['posts'] ));
        $this->assertEmpty($user['posts']);
        $this->assertNull($user['lastPost']);

        $em->clear();
        /**
         * @var UserTest $user
         */
        $user = $userRepository->findBy()
            ->first()
            ->orderBy('id', 'DESC')
            ->with(PostTest::class)
            ->toObject();


        $this->assertStrictEquals( 5, $user->getId() );
        $this->assertEmpty($user->getPosts()->toArray());
        $this->assertNull($user->getLastPost());

        $em->clear();
        $users = $userRepository->findBy()->orderBy('id', 'DESC')->with(PostTest::class)->toArray();
        $this->assertStrictEquals( 5, $users[0]['id'] );
        $this->assertStrictEquals(5, count($users));

        $em->clear();
        $users = $userRepository->findBy()->orderBy('id', 'DESC')->with(PostTest::class)->toObject();

        $this->assertStrictEquals( 5, $users[0]->getId() );
        $this->assertStrictEquals(5, count($users));
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            if ($user->getId() === 5) {
                $this->assertNull($user->getLastPost());
                $this->assertEmpty($user->getPosts()->toArray());
                continue;
            }
            $this->assertInstanceOf(PostTest::class, $user->getLastPost());
            $this->assertEmpty($user->getLastPost()->getTags()->toArray());

            $this->assertNotEmpty($user->getPosts()->toArray());
            foreach ($user->getPosts() as $post) {
                $this->assertInstanceOf(PostTest::class, $post);
                $this->assertNull($post->getUser());
                $this->assertEmpty($post->getTags()->toArray());
            }
        }

        $em->clear();
        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->with('posts.tags')
            ->toObject();
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            if ($user->getId() === 5) {
                $this->assertEmpty($user->getPosts()->toArray());
            }else {
                $this->assertNotEmpty($user->getPosts()->toArray());
            }
            $this->assertNull($user->getLastPost());
            foreach ($user->getPosts() as $post) {
                $this->assertInstanceOf(PostTest::class, $post);
                $this->assertNull($post->getUser());
                $this->assertNotEmpty($post->getTags()->toArray());
            }
        }

        $em->clear();
        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->with('posts.tags')
            ->with('lastPost.tags')
            ->toObject();
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            if ($user->getId() === 5) {
                $this->assertNull($user->getLastPost());
                $this->assertEmpty($user->getPosts()->toArray());
            }else {
                $this->assertInstanceOf(PostTest::class, $user->getLastPost());
                $this->assertNotEmpty($user->getPosts()->toArray());
                $this->assertNotEmpty($user->getLastPost()->getTags()->toArray());
            }
            foreach ($user->getPosts() as $post) {
                $this->assertInstanceOf(PostTest::class, $post);
                $this->assertNull($post->getUser());
                $this->assertNotEmpty($post->getTags()->toArray());
            }
        }
    }

}
