<?php

namespace Test\Michel\PaperORM\Entity;

use DateTime;
use Michel\PaperORM\Collection\ObjectStorage;
use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Mapping\Column\SlugColumn;
use Michel\PaperORM\Mapping\Column\StringColumn;
use Michel\PaperORM\Mapping\Column\DateTimeColumn;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Michel\PaperORM\Mapping\Entity;
use Michel\PaperORM\Mapping\OneToMany;
use Test\Michel\PaperORM\Repository\PostTestRepository;

#[Entity(table : 'post', repository : PostTestRepository::class)]
class PostTest implements EntityInterface
{
    #[PrimaryKeyColumn]
    private ?int $id = null;

    #[StringColumn]
    private ?string $title = null;

    #[StringColumn]
    private ?string $content = null;

    #[SlugColumn(from : ['title'])]
    private ?string $slug = null;

    #[DateTimeColumn()]
    private ?DateTime $createdAt = null;

    #[JoinColumn(name: 'user_id', targetEntity:  UserTest::class, nullable: true, unique: false, onDelete: JoinColumn::SET_NULL)]
    private ?UserTest $user = null;

    #[OneToMany(targetEntity: TagTest::class, mappedBy: 'post')]
    private ObjectStorage $tags;
    #[OneToMany(targetEntity: CommentTest::class, mappedBy: 'post')]
    private ObjectStorage $comments;

    public function __construct()
    {
        $this->tags = new ObjectStorage();
        $this->comments = new ObjectStorage();
    }

    static public function getTableName(): string
    {
        return 'post';
    }

    static public function getRepositoryName(): string
    {
        return PostTestRepository::class;
    }

    static public function columnsMapping(): array
    {
        if (PHP_VERSION_ID > 80000) {
            return [];
        }
        return [
            (new PrimaryKeyColumn())->bindProperty('id'),
            (new StringColumn())->bindProperty('title'),
            (new StringColumn())->bindProperty('content'),
            (new SlugColumn(null, ['title']))->bindProperty('slug'),
            (new DateTimeColumn( null))->bindProperty('createdAt'),
            (new JoinColumn('user_id', UserTest::class, 'id', true, false, JoinColumn::SET_NULL))->bindProperty('user'),
            (new OneToMany( TagTest::class, 'post'))->bindProperty('tags'),
            (new OneToMany( CommentTest::class, 'post'))->bindProperty('comments'),
        ];
    }

    public function getPrimaryKeyValue(): ?int
    {
        return $this->getId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): PostTest
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): PostTest
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): PostTest
    {
        $this->content = $content;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): PostTest
    {
        $this->slug = $slug;
        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): PostTest
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUser(): ?UserTest
    {
        return $this->user;
    }

    public function setUser(?UserTest $user): PostTest
    {
        $this->user = $user;
        return $this;
    }

    public function getTags(): ObjectStorage
    {
        return $this->tags;
    }

    public function addTag(TagTest $tag): PostTest
    {
        $this->tags->add($tag);
        return $this;
    }

    public function getComments(): ObjectStorage
    {
        return $this->comments;
    }

    public function addComment(CommentTest $comment): PostTest
    {
        $this->comments->add($comment);
        return $this;
    }
}
