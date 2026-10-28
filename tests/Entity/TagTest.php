<?php

namespace Test\Michel\PaperORM\Entity;

use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Mapping\Column\StringColumn;
use Michel\PaperORM\Mapping\Column\DateTimeColumn;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Michel\PaperORM\Mapping\Entity;
use Test\Michel\PaperORM\Repository\PostTestRepository;
use Test\Michel\PaperORM\Repository\TagTestRepository;

#[Entity(table : 'tag', repository : TagTestRepository::class)]
class TagTest implements EntityInterface
{
    #[PrimaryKeyColumn]
    private ?int $id = null;
    #[StringColumn]
    private ?string $name = null;

    #[JoinColumn(name : 'post_id', targetEntity : PostTest::class)]
    private ?PostTest $post = null;

    public function getPrimaryKeyValue() : ?int
    {
        return $this->getId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): TagTest
    {
        $this->name = $name;
        return $this;
    }

    public function getPost(): ?PostTest
    {
        return $this->post;
    }

    public function setPost(?PostTest $post): TagTest
    {
        $this->post = $post;
        return $this;
    }
}
