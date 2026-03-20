<?php

namespace Test\Michel\PaperORM\Entity;

use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Entity\TableMetadataInterface;
use Michel\PaperORM\Mapping\Column\StringColumn;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Michel\PaperORM\Mapping\Column\UuidColumn;
use Michel\PaperORM\Mapping\Entity;
use Test\Michel\PaperORM\Repository\TagTestRepository;

#[Entity(table : 'comment', repository : null)]
class CommentTest implements EntityInterface, TableMetadataInterface
{
    private ?int $id = null;
    private ?string $body = null;
    private ?string $uuid = null;
    private ?PostTest $post = null;

    static public function getTableName(): string
    {
        return 'comment';
    }

    static public function getRepositoryName(): ?string
    {
        return null;
    }

    static public function columnsMapping(): array
    {
        return [
            (new PrimaryKeyColumn())->bindProperty('id'),
            (new StringColumn())->bindProperty('body'),
            (new UuidColumn())->bindProperty('uuid'),
            (new JoinColumn('post_id', PostTest::class))->bindProperty('post'),
        ];
    }

    public function getPrimaryKeyValue() : ?int
    {
        return $this->getId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): CommentTest
    {
        $this->body = $body;
        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid): CommentTest
    {
        $this->uuid = $uuid;
        return $this;
    }


    public function getPost(): ?PostTest
    {
        return $this->post;
    }

    public function setPost(?PostTest $post): CommentTest
    {
        $this->post = $post;
        return $this;
    }

    static public function getIndexes(): array
    {
        return [];
    }
}
