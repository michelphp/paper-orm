<?php

namespace Test\Michel\PaperORM\Entity;

use Cassandra\Time;
use Michel\PaperORM\Collection\ObjectStorage;
use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Mapping\Column\BoolColumn;
use Michel\PaperORM\Mapping\Column\StringColumn;
use Michel\PaperORM\Mapping\Column\DateTimeColumn;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Michel\PaperORM\Mapping\Column\TimestampColumn;
use Michel\PaperORM\Mapping\Column\TokenColumn;
use Michel\PaperORM\Mapping\Entity;
use Michel\PaperORM\Mapping\OneToMany;
use Test\Michel\PaperORM\Repository\PostTestRepository;

#[Entity(table : 'user', repository : null)]
class UserTest implements EntityInterface
{
    #[PrimaryKeyColumn]
    private ?int $id = null;

    #[StringColumn]
    private ?string $firstname = null;

    #[StringColumn]
    private ?string $lastname  = null;

    #[StringColumn]
    private ?string $email = null;

    #[StringColumn]
    private ?string $password = null;

    #[TokenColumn(length: 32)]
    private ?string $token = null;

    #[BoolColumn(name: 'is_active')]
    private bool $active = false;

    #[TimestampColumn( onCreated: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[OneToMany(targetEntity: PostTest::class, mappedBy: 'user')]
    private ObjectStorage $posts;

    #[JoinColumn(name: 'last_post_id', targetEntity: PostTest::class, nullable: true, unique: true, onDelete: JoinColumn::SET_NULL)]
    private ?PostTest $lastPost = null;
    public function __construct()
    {
        $this->posts = new ObjectStorage();
    }

    static public function getTableName(): string
    {
        return 'user';
    }

    static public function getRepositoryName(): ?string
    {
        return null;
    }

    static public function columnsMapping(): array
    {
        if (PHP_VERSION_ID > 80000) {
            return [];
        }
        return [
            (new PrimaryKeyColumn())->bindProperty('id'),
            (new StringColumn())->bindProperty('firstname'),
            (new StringColumn())->bindProperty('lastname'),
            (new StringColumn())->bindProperty('email'),
            (new StringColumn())->bindProperty('password'),
            (new TokenColumn(null, 32))->bindProperty('token'),
            (new BoolColumn( 'is_active'))->bindProperty('active'),
            (new TimestampColumn( null, true))->bindProperty('createdAt'),
            (new OneToMany( PostTest::class,  'user'))->bindProperty('posts'),
            (new JoinColumn( 'last_post_id', PostTest::class, 'id', true, true, JoinColumn::SET_NULL))->bindProperty('lastPost'),
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

    public function setId(?int $id): UserTest
    {
        $this->id = $id;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): UserTest
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): UserTest
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): UserTest
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): UserTest
    {
        $this->password = $password;
        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): UserTest
    {
        $this->token = $token;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): UserTest
    {
        $this->active = $active;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): UserTest
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getPosts(): ObjectStorage
    {
        return $this->posts;
    }

    public function addPost(PostTest $post): UserTest
    {
        $this->posts->add($post);
        return $this;
    }

    public function getLastPost(): ?PostTest
    {
        return $this->lastPost;
    }

    public function setLastPost(?PostTest $lastPost): UserTest
    {
        $this->lastPost = $lastPost;
        return $this;
    }
}
