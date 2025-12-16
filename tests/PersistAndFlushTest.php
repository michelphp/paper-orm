<?php

namespace Test\Michel\PaperORM;

use Michel\PaperORM\EntityManager;
use Michel\PaperORM\Expression\Expr;
use Michel\PaperORM\PaperConfiguration;
use Michel\PaperORM\Proxy\ProxyInterface;
use Michel\PaperORM\Tools\IDBuilder;
use Michel\UniTester\TestCase;
use Test\Michel\PaperORM\Entity\CommentTest;
use Test\Michel\PaperORM\Entity\InvoiceTest;
use Test\Michel\PaperORM\Entity\PostTest;
use Test\Michel\PaperORM\Entity\UserTest;
use Test\Michel\PaperORM\Helper\DataBaseHelperTest;

class PersistAndFlushTest extends TestCase
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
            DataBaseHelperTest::init($em);
            $this->testInsert($em);
            $this->testInsertAndUpdate($em);
            $this->testUpdate($em);
            $this->testUpdateJoinColumn($em);
            $this->testDelete($em);
            $em->getConnection()->close();
        }
    }



    private function testInsert(EntityManager $em): void
    {
        $user = new UserTest();
        $this->assertNull($user->getId());
        $this->assertNull($user->getToken());
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setPassword('secret');
        $user->setEmail('Xq5qI@example.com');
        $user->setActive(true);
        $em->persist($user);
        $em->flush();


        $this->assertStringLength($user->getToken(), 32);
        $this->assertNotNull($user->getId());
        $this->assertInstanceOf(\DateTimeInterface::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $user->getCreatedAt());
        $em->clear();

        $post = new PostTest();
        $post->setUser($user);
        $post->setTitle('Hello World !, it\'s me');
        $post->setContent('Hello World !');
        $this->assertNull($post->getSlug());
        $em->persist($post);
        $em->flush();

        $this->assertStrictEquals('hello-world-it-s-me', $post->getSlug());
        $em->clear();


        $post = new PostTest();
        $post->setUser($user);
        $post->setTitle('Hello World !, it\'s me');
        $post->setContent('Hello World !');
        $post->setSlug('my-slug');
        $em->persist($post);
        $em->flush();

        $this->assertStrictEquals('my-slug', $post->getSlug());
        $em->clear();

        $comment = new CommentTest();
        $this->assertNull($comment->getUuid());
        $comment->setPost($post);
        $comment->setBody("my comment");
        $em->persist($comment);
        $em->flush();
        $this->assertNotNull($comment->getUuid());

        $uuid = $comment->getUuid();
        $em->clear();

        $comment = $em->getRepository(CommentTest::class)
            ->findOneBy(['uuid' => $uuid])
            ->with('post.user')
            ->toReadOnlyObject()
        ;
        $this->assertNotNull($comment);
        $this->assertEquals($uuid, $comment->getUuid());
        $this->assertInstanceOf(CommentTest::class, $comment);
        $this->assertNotNull($comment->getUuid());
        $this->assertNotNull($comment->getPost());
        $this->assertInstanceOf(PostTest::class, $comment->getPost());
        $this->assertNotNull($comment->getPost()->getId());
        $this->assertNotNull($comment->getPost()->getUser());
        $this->assertInstanceOf(UserTest::class, $comment->getPost()->getUser());
        $this->assertNotNull($comment->getPost()->getUser()->getId());

        $keyNumber = 'invoice.number.'.IDBuilder::generate('INV-{YYYY}-');
        $keyCode = 'invoice.code';
        $em->sequence()->reset($keyNumber);
        $em->sequence()->reset($keyCode);
        $em->sequence()->increment($keyCode);

        for ($i = 0; $i < 10; $i++) {
            $peekNumber = $em->sequence()->peek($keyNumber);
            $peekCode = $em->sequence()->peek($keyCode);
            $invoice = new InvoiceTest();
            $this->assertEmpty($invoice->getCode());
            $this->assertEmpty($invoice->getNumber());
            $em->persist($invoice);
            $em->flush();
            $this->assertNotEmpty($invoice->getCode());
            $this->assertNotEmpty($invoice->getNumber());
            $this->assertEquals($em->sequence()->peek($keyNumber), $peekNumber + 1);
            $this->assertEquals($em->sequence()->peek($keyCode), $peekCode + 1);
        }

        $this->assertEquals(11, $em->sequence()->peek($keyNumber));
        $this->assertEquals(12, $em->sequence()->peek($keyCode));

    }


    private function testInsertAndUpdate(EntityManager $em): void
    {
        $user = new UserTest();
        $this->assertNull($user->getToken());
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setPassword('secret');
        $user->setEmail('Xq5qI@example.com');
        $user->setActive(true);
        $em->persist($user);
        $em->flush();

        $token = $user->getToken();
        $this->assertNotNull($token);
        $this->assertNotNull($user->getId());
        $this->assertInstanceOf(\DateTimeInterface::class, $user->getCreatedAt());
        $user->setLastname('TOTO');
        $em->persist($user);
        $em->flush();
        $em->clear();

        $this->assertEquals($token, $user->getToken());
    }

    private function testUpdate(EntityManager $em): void
    {
        $userRepository = $em->getRepository(UserTest::class);
        $user = $userRepository->findBy()->first()->orderBy('id')->toObject();
        $this->assertInstanceOf(ProxyInterface::class, $user);
        $this->assertInstanceOf(UserTest::class, $user);
        $this->assertStrictEquals(UserTest::class, $user->__getParentClass());
        /**
         * @var ProxyInterface|UserTest $user
         */
        $user->setActive(false);
        $user->setLastname('TOTO');
        $this->assertStrictEquals(2, count($user->__getPropertiesModified()));

        $em->persist($user);
        $em->flush();
        $user = null;
        $em->clear();

        $user = $userRepository->findBy()->first()->orderBy('id')->toObject();
        $this->assertInstanceOf(ProxyInterface::class, $user);
        $this->assertInstanceOf(UserTest::class, $user);
        /**
         * @var ProxyInterface|UserTest $user
         */
        $this->assertStrictEquals(0, count($user->__getPropertiesModified()));
        $this->assertFalse($user->isActive());
        $this->assertStrictEquals('TOTO', $user->getLastname());
    }


    private function testUpdateJoinColumn(EntityManager $em)
    {
        $userRepository = $em->getRepository(UserTest::class);
        $postRepository = $em->getRepository(PostTest::class);
        $post = $postRepository->findBy()->first()
            ->where(Expr::isNotNull('user'))
            ->with(UserTest::class)
            ->toObject();
        $this->assertInstanceOf(ProxyInterface::class, $post);
        $this->assertInstanceOf(PostTest::class, $post);
        $this->assertInstanceOf(UserTest::class, $post->getUser());
        $this->assertStrictEquals(1, $post->getUser()->getId());

        $user2 = $userRepository->find(2)
            ->with(PostTest::class)
            ->toObject();
        $this->assertStrictEquals(2, count($user2->getPosts()->toArray()));
        foreach ($user2->getPosts()->toArray() as $postItem) {
            $this->assertInstanceOf(ProxyInterface::class, $postItem);
            $this->assertInstanceOf(PostTest::class, $postItem);
        }
        $post->setUser($user2);
        $em->persist($post);
        $em->flush();
        $user2 = $userRepository->find(2)
            ->with(PostTest::class)
            ->toObject();
        $this->assertStrictEquals(3, count($user2->getPosts()->toArray()));

        $user1 = $userRepository->find(1)->with(PostTest::class)->toObject();
        $this->assertStrictEquals(1, count($user1->getPosts()->toArray()));
    }

    private function testDelete(EntityManager $em)
    {
        $user = $em->getRepository(UserTest::class)->find(1)->toObject();
        $this->assertInstanceOf(ProxyInterface::class, $user);
        $this->assertInstanceOf(UserTest::class, $user);

        $em->remove($user);
        $em->flush();
        $this->assertFalse($user->__isInitialized());

        $posts = $user->getPosts();
        $ids = [];
        foreach ($posts as $post) {
            $ids[] = $post->getId();
            $em->remove($post);
            $em->flush();
            $this->assertFalse($post->__isInitialized());
        }
        $user = $em->getRepository(UserTest::class)->find(1)->toObject();
        $this->assertNull($user);

        foreach ($ids as $idPost) {
            $postToDelete = $em->getRepository(PostTest::class)->find($idPost)->toObject();
            $this->assertNull($postToDelete);
        }
    }
}
