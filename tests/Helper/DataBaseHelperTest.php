<?php

namespace Test\Michel\PaperORM\Helper;

use DateTime;
use Michel\PaperORM\EntityManagerInterface;
use Michel\PaperORM\Generator\SchemaDiffGenerator;
use Michel\PaperORM\Internal\Entity\PaperKeyValue;
use Michel\PaperORM\Mapper\ColumnMapper;
use Michel\PaperORM\Mapping\Column\BoolColumn;
use Michel\PaperORM\Mapping\Column\DateTimeColumn;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Michel\PaperORM\Mapping\Column\SlugColumn;
use Michel\PaperORM\Mapping\Column\StringColumn;
use Michel\PaperORM\Mapping\Column\TimestampColumn;
use Michel\PaperORM\Mapping\Column\TokenColumn;
use Michel\PaperORM\Mapping\Column\UuidColumn;
use Michel\PaperORM\Tools\IDBuilder;
use Test\Michel\PaperORM\Entity\InvoiceTest;
use Test\Michel\PaperORM\Entity\PostTest;
use Test\Michel\PaperORM\Entity\UserTest;

class DataBaseHelperTest
{

    public static function drivers(): array
    {
        return [
            'sqlite' => [
                'driver' => 'sqlite',
                'user' => null,
                'password' => null,
                'memory' => true,
            ],
            'mariadb' => [
                'driver' => 'mariadb',
                'host' => getenv('MARIADB_HOST') ?: '127.0.0.1',
                'port' => (int)(getenv('MARIADB_PORT') ?: 3306),
                'path' => getenv('MARIADB_DB') ?: 'paper_orm_test',
                'user' => getenv('MARIADB_USER') ?: 'root',
                'password' => getenv('MARIADB_PASSWORD') ?: '',
                'charset' => 'utf8mb4',
            ],
        ];
    }
    public static function init(EntityManagerInterface $entityManager, int $nbUsers = 5, bool $withPosts = true)
    {
        $userColumns = [
            new PrimaryKeyColumn('id'),
            (new JoinColumn('last_post_id', PostTest::class, 'id', true, true)),
            new StringColumn('firstname'),
            new StringColumn('lastname'),
            new StringColumn('email'),
            new StringColumn('password'),
            new TokenColumn('token', 32),
            new BoolColumn('is_active'),
            new TimestampColumn('created_at', true),
        ];
        $postColumns = [
            new PrimaryKeyColumn('id'),
            (new JoinColumn('user_id', UserTest::class, 'id', true, false, JoinColumn::SET_NULL)),
            new StringColumn('title'),
            new StringColumn('content'),
            new SlugColumn('slug', ['title']),
            new TimestampColumn('created_at', true),
        ];

        $tagColumns = [
            new PrimaryKeyColumn('id'),
            (new JoinColumn('post_id', PostTest::class, 'id', true, false, JoinColumn::SET_NULL)),
            new StringColumn('name'),
        ];
        $commentColumns = [
            new PrimaryKeyColumn('id'),
            (new JoinColumn('post_id', PostTest::class, 'id', true, false, JoinColumn::SET_NULL)),
            new StringColumn('body'),
            new UuidColumn('uuid'),
        ];


        $platform = $entityManager->getPlatform();
        $platform->createDatabaseIfNotExists();
        $platform->dropDatabase();
        $platform->createDatabaseIfNotExists();
        $statements = (new SchemaDiffGenerator($platform))->generateDiffStatements([
                'user' => [
                    'columns' => $userColumns,
                    'indexes' => [],
                ],
                'post' => [
                    'columns' => $postColumns,
                    'indexes' => [],
                ],
                'tag' => [
                    'columns' => $tagColumns,
                    'indexes' => [],
                ],
                'comment' => [
                    'columns' => $commentColumns,
                    'indexes' => [],
                ],
                'invoice' => [
                    'columns' => ColumnMapper::getColumns(InvoiceTest::class),
                    'indexes' => [],
                ],
                'paper_key_value' => [
                    'columns' => ColumnMapper::getColumns(PaperKeyValue::class),
                    'indexes' => [],
                ]
            ]
        );


        $connection = $entityManager->getConnection();
        $connection->close();
        $connection->connect();
        foreach ($statements['up'] as $statement) {
            $connection->executeStatement($statement);
        }

        for ($i = 0; $i < $nbUsers; $i++) {
            $user = [
                'firstname' => 'John' . $i,
                'lastname' => 'Doe' . $i,
                'email' => $i . 'bqQpB@example.com',
                'password' => 'password123',
                'token' => bin2hex(random_bytes(16)),
                'is_active' => true,
                'created_at' => (new DateTime())->format($platform->getSchema()->getDateTimeFormatString()),
            ];

            $stmt = $connection->getPdo()->prepare("INSERT INTO user (firstname, lastname, email, password, token, is_active, created_at) VALUES (:firstname, :lastname, :email, :password, :token,:is_active, :created_at)");
            $stmt->execute([
                'firstname' => $user['firstname'],
                'lastname' => $user['lastname'],
                'email' => $user['email'],
                'password' => $user['password'],
                'token' => $user['token'],
                'is_active' => $user['is_active'],
                'created_at' => $user['created_at']
            ]);
        }
//
        if ($withPosts) {
            $nbPosts = $nbUsers - 1;
            for ($i = 0; $i < $nbPosts; $i++) {
                $id = uniqid('post_', true);
                $post = [
                    'user_id' => $i + 1,
                    'title' => 'Post ' . $id,
                    'content' => 'Content ' . $id,
                    'slug' => 'post-' . $id,
                    'created_at' => (new DateTime())->format($platform->getSchema()->getDateTimeFormatString()),
                ];

                $stmt = $connection->getPdo()->prepare("INSERT INTO post (user_id, title, content, slug, created_at)  VALUES (:user_id, :title, :content, :slug, :created_at)");
                $stmt->execute([
                    'user_id' => $post['user_id'],
                    'title' => $post['title'],
                    'content' => $post['content'],
                    'slug' => $post['slug'],
                    'created_at' => $post['created_at']
                ]);
                $id = uniqid('post_', true);
                $post = [
                    'user_id' => $i + 1,
                    'title' => 'Post ' . $id,
                    'content' => 'Content ' . $id,
                    'slug' => 'post-' . $id,
                ];
                $connection->executeStatement("INSERT INTO post (user_id, title, content, slug) VALUES (
                '{$post['user_id']}',
                '{$post['title']}',
                '{$post['content']}',
                '{$post['slug']}'
            )");

                $lastId = $connection->getPdo()->lastInsertId();
                $connection->executeStatement('UPDATE user SET last_post_id = ' . $lastId . ' WHERE id = ' . $post['user_id']);
            }


            $nbTags = $nbPosts * 2;
            for ($i = 0; $i < $nbTags; $i++) {
                $id = uniqid('tag_', true);
                $tag = [
                    'post_id' => $i + 1,
                    'name' => 'Tag ' . $id,
                ];
                $connection->executeStatement("INSERT INTO tag (post_id, name) VALUES (
                '{$tag['post_id']}',
                '{$tag['name']}      '
            )");

                $id = uniqid('tag_', true);
                $tag = [
                    'post_id' => $i + 1,
                    'name' => 'Tag ' . $id,
                ];
                $connection->executeStatement("INSERT INTO tag (post_id, name) VALUES (
                '{$tag['post_id']}',
                '{$tag['name']}      '
            )");
            }
            $nbComments = $nbTags - 1;
            for ($i = 0; $i < $nbComments; $i++) {
                $id = uniqid('comment_', true);
                $comment = [
                    'post_id' => $i + 1,
                    'body' => 'Comment ' . $id,
                    'uuid' => IDBuilder::generate('{UUID}')
                ];
                $connection->executeStatement("INSERT INTO comment (post_id, body, uuid) VALUES (
                '{$comment['post_id']}',
                '{$comment['body']}',
                '{$comment['uuid']}'
            )");


                $comment['body'] = 'Comment ' . $id . ' 2';
                $comment['uuid'] = IDBuilder::generate('{UUID}');

                $connection->executeStatement("INSERT INTO comment (post_id, body, uuid) VALUES (
                '{$comment['post_id']}',
                '{$comment['body']}',
                '{$comment['uuid']}'
            )");
            }
        }


    }

}
