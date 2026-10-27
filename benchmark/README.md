# PaperORM vs Doctrine ORM — Benchmark

Standalone project, **not part of the `michel/paper-orm` package**:

- Doctrine is only required here (`benchmark/composer.json`), never in PaperORM's `require` / `require-dev`.
- `vendor/`, `composer.lock`, `var/` and `results/` are git-ignored.
- The folder is `export-ignore`d (see `/.gitattributes`), so it is not shipped in the Packagist archive.
- PaperORM is linked from `..` through a Composer `path` repository (symlink): the benchmark always runs **your current working copy**.

Requires PHP >= 8.1 with `pdo_sqlite`.

## Docker (PHP 8.2, Debian)

`Dockerfile` + `docker-compose.yml` give a reproducible PHP 8.2 CLI (Debian bookworm) with Composer and the
SPX profiler. The repositories are mounted under `/src` with the same layout as on the host
(`/src/paper-orm`, `/src/php-sql-mapper`) and `/app` is the benchmark directory.

```bash
cd benchmark
docker compose build
docker compose run --rm bench composer install
docker compose run --rm bench php bench.php --iterations=10
docker compose run --rm bench php profile.php
```

Profile `find()` function by function with SPX (loaded only for this command):

```bash
docker compose run --rm -e SPX_ENABLED=1 -e SPX_REPORT=flat -e SPX_FP_LIMIT=30 bench \
    php -d extension=spx.so profile-find.php
```

The `php-sql-mapper` mount expects that directory next to `paper-orm`; remove it from `docker-compose.yml`
if you do not use a local php-sql-mapper.

The compose file also starts a MariaDB (data in memory, gone when stopped) for the paper-orm test suite:

```bash
docker compose run --rm bench sh -c "cd /src/paper-orm && php vendor/bin/unitester tests/"
docker compose down
```

## Install

```bash
cd benchmark
composer install
```

## Runtime benchmark

```bash
php bench.php                          # every scenario, N=1000, 5 iterations
php bench.php --n=5000 --iterations=10
php bench.php --scenario=join,paginate --orm=paper
```

| Scenario         | What is measured                                             |
|------------------|--------------------------------------------------------------|
| `bootstrap`      | Build the entity manager, then fetch one entity (classes already loaded) |
| `insert`         | `persist()` N new users, then a single `flush()`             |
| `find_pk`        | Fetch N users one by one by primary key                      |
| `find_all`       | Fetch N users as objects                                     |
| `find_all_array` | Fetch N users as arrays                                      |
| `join`           | Fetch N users with their posts (one-to-many, single query)   |
| `paginate`       | Fetch 10 pages of 20 users ordered by id                     |
| `paginate_join`  | Same pages, each user with all its posts (Doctrine: `Paginator`) |
| `update`         | Modify N managed users, then a single `flush()`              |
| `delete`         | `remove()` N managed users, then a single `flush()`          |

Fairness rules:

- Everything runs in **one PHP process**: no process spawning cost, same conditions for both ORMs.
- The ORM order **alternates on every iteration** (A B, B A, ...), so neither the execution order nor a drift of the machine favours one of them.
- Every measurement gets its own entity manager and its own **in-memory SQLite** database.
- Same raw DDL and same seeded data for both ORMs (`src/Schema.php`), no schema tool involved.
- Metadata is cached for the whole process for both ORMs (PaperORM static caches, one shared Doctrine cache), like a persistent cache in production.
- Loading entities is excluded from `update` / `delete`, only the write is measured.
- Each result is **verified** (row counts, sum of ids for pagination). A wrong result is flagged with `(!)`.
- 1 discarded warm-up iteration (class loading, metadata), then the median and the minimum of `--iterations` runs.
- Peak memory needs PHP >= 8.2 (`memory_reset_peak_usage()`).

`profile.php` breaks the PaperORM read path down layer by layer (raw SQL, JoinQL / GraphBuilder, hydration modes).

Disable Xdebug when benchmarking. Results are saved in `results/bench-latest.json`.

## Size benchmark

```bash
php size.php
php size.php --composer=/path/to/composer.phar
```

Installs each ORM **alone** in an empty Composer project (`--no-dev`, dist archives), exactly like
`composer require` in a user project, and measures the whole `vendor/` directory: size, files,
PHP files and PHP lines, plus a per-package breakdown.
PaperORM is measured from its **published** Packagist version. Results are saved in `results/size-latest.json`.

## Using your local php-sql-mapper

By default `michel/php-sql-mapper` comes from Packagist. To benchmark a local copy:

```bash
composer config repositories.sql-mapper path ../../php-sql-mapper
composer require "michel/php-sql-mapper:dev-main as 1.99.0"
```

Revert with `git checkout composer.json`.
