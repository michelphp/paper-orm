<?php

declare(strict_types=1);

namespace Bench\Entity\Paper;

use Michel\PaperORM\Collection\ObjectStorage;
use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Mapping\Column\IntColumn;
use Michel\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Michel\PaperORM\Mapping\Column\StringColumn;
use Michel\PaperORM\Mapping\Entity;
use Michel\PaperORM\Mapping\OneToMany;

#[Entity(table: 'users')]
class User implements EntityInterface
{
    #[PrimaryKeyColumn]
    private ?int $id = null;

    #[StringColumn]
    private string $name = '';

    #[StringColumn]
    private string $email = '';

    #[IntColumn]
    private int $age = 0;

    #[OneToMany(targetEntity: Post::class, mappedBy: 'user')]
    private ObjectStorage $posts;

    public function __construct()
    {
        $this->posts = new ObjectStorage();
    }

    public function getPrimaryKeyValue(): ?int
    {
        return $this->id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): self
    {
        $this->age = $age;
        return $this;
    }

    public function getPosts(): ObjectStorage
    {
        return $this->posts;
    }
}
