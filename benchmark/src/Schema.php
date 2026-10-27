<?php

declare(strict_types=1);

namespace Bench;

/**
 * Same raw DDL and data for both ORMs, so that neither schema tool influences the results.
 */
final class Schema
{
    public const AGE_INCREMENT = 100;
    private const AGE_MIN = 18;
    private const AGE_RANGE = 60;

    public static function create(\PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, age INTEGER NOT NULL)');
        $pdo->exec('CREATE TABLE posts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NULL REFERENCES users (id), title VARCHAR(255) NOT NULL)');
        $pdo->exec('CREATE INDEX idx_posts_user_id ON posts (user_id)');
    }

    public static function seed(\PDO $pdo, int $users, int $postsPerUser): void
    {
        $pdo->beginTransaction();
        $insertUser = $pdo->prepare('INSERT INTO users (name, email, age) VALUES (?, ?, ?)');
        $insertPost = $pdo->prepare('INSERT INTO posts (user_id, title) VALUES (?, ?)');
        for ($i = 1; $i <= $users; $i++) {
            $insertUser->execute(['User ' . $i, 'user' . $i . '@example.com', self::age($i)]);
            for ($p = 1; $p <= $postsPerUser; $p++) {
                $insertPost->execute([$i, sprintf('Post %d of user %d', $p, $i)]);
            }
        }
        $pdo->commit();
    }

    public static function age(int $i): int
    {
        return self::AGE_MIN + ($i % self::AGE_RANGE);
    }

    /**
     * Seeded ages stay below this value, updated ages are all greater or equal.
     */
    public static function updatedAgeThreshold(): int
    {
        return self::AGE_MIN + self::AGE_INCREMENT;
    }

    public static function count(\PDO $pdo, string $sql): int
    {
        return (int) $pdo->query($sql)->fetchColumn();
    }
}
