# PaperORM

**The ORM that does what you wrote, and nothing else.**

Map your entities with attributes, query them through repositories, migrate your schema. No lazy loading, no cascade, no transaction you did not open. Every query runs because you asked for it.

PHP 8.2+ · SQLite · MariaDB / MySQL · [Symfony bundle](https://github.com/michelphp/paper-orm-bundle)

📖 **[Documentation](https://michelphp.github.io/paper-orm/docs.html)** · [Website](https://michelphp.github.io/paper-orm/)

## Install

```bash
composer require michel/paper-orm          # any PHP project
composer require michel/paper-orm-bundle   # Symfony
```

## Quick start

```php
use Michel\PaperORM\EntityManager;
use Michel\PaperORM\PaperConfiguration;

$em = EntityManager::createFromConfig(
    PaperConfiguration::fromDsn('sqlite:///var/data/app.sqlite')
);
```

```php
use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Mapping\Entity;
use Michel\PaperORM\Mapping\Column\{PrimaryKeyColumn, StringColumn, BoolColumn};

#[Entity(table: 'user')]
class User implements EntityInterface
{
    #[PrimaryKeyColumn]
    private ?int $id = null;

    #[StringColumn]
    private ?string $email = null;

    #[BoolColumn(name: 'is_active')]
    private bool $active = true;

    public function getPrimaryKeyValue(): ?int { return $this->id; }

    // getters and setters...
}
```

```php
$em->persist((new User())->setEmail('jane@example.com'));
$em->flush();

$users = $em->getRepository(User::class)
    ->findBy(['active' => true])
    ->orderBy('email')
    ->limit(20)
    ->toObject();            // User[]
```

## Why PaperORM

| | |
|---|---|
| **No surprise query** | No lazy loading: a relation is loaded with `with()` or `has()`, in the same query. Nothing runs when you read a property. |
| **Paginate one-to-many** | `limit(10)` returns 10 users, even when each brings 50 posts in the same join. Filter and sort on relations, the page stays right. |
| **Your transactions** | `flush()` never opens a transaction behind your back. |
| **Migrations as SQL** | `paper:migration:diff` writes a plain `.sql` file you can read; `paper:migration:migrate` runs it. |
| **Small and fast** | About 21,000 lines of PHP with its dependencies, against about 171,000 for Doctrine ORM. Faster than or level with Doctrine on every scenario of the [bundled benchmark](benchmark/). |

## What PaperORM will never do

lazy-load a relation · cascade a persist or a delete · open a transaction for you · invent a query language · query when you read a property · require a framework

## Documentation

The [documentation](https://michelphp.github.io/paper-orm/docs.html) covers configuration, entities and relations, migrations, persistence, generated values, repositories, pagination, the query builder, transactions, events, the registry and the Symfony bundle.

## Status

PaperORM is in **alpha**: the API may still change between `0.x` versions. Bug reports and ideas are welcome in the [issues](https://github.com/michelphp/paper-orm/issues).

## License

[MPL-2.0](LICENSE)
