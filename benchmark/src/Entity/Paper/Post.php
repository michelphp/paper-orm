<?php

declare(strict_types=1);

namespace Bench\Entity\Paper;

use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Michel\PaperORM\Mapping\Column\StringColumn;
use Michel\PaperORM\Mapping\Entity;

#[Entity(table: 'posts')]
class Post implements EntityInterface
{
    #[PrimaryKeyColumn]
    private ?int $id = null;

    #[JoinColumn(name: 'user_id', targetEntity: User::class, nullable: true)]
    private ?User $user = null;

    #[StringColumn]
    private string $title = '';

    public function getPrimaryKeyValue(): ?int
    {
        return $this->id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }
}
