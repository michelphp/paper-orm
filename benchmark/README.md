# PaperORM vs Doctrine ORM — Benchmark

Standalone project, **not part of the `michel/paper-orm` package**:

- Doctrine is only required here (`benchmark/composer.json`), never in PaperORM's `require` / `require-dev`.
- `vendor/`, `composer.lock`, `var/` and `results/` are git-ignored.
- The folder is `export-ignore`d (see `/.gitattributes`), so it is not shipped in the Packagist archive.
- PaperORM is linked from `..` through a Composer `path` repository (symlink): the benchmark always runs **your current working copy**.

Requires PHP >= 8.1 with `pdo_sqlite`.

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
| `bootstrap`      | Build the entity manager, then fetch one entity (cold start) |
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

- Every measurement runs in a **fresh PHP process** with its own **in-memory SQLite** database.
- Same raw DDL and same seeded data for both ORMs (`src/Schema.php`), no schema tool involved.
- Metadata is loaded once before the measurement (except `bootstrap`, which measures exactly that).
- Loading entities is excluded from `update` / `delete`, only the write is measured.
- Doctrine uses in-process array caches only, like PaperORM: nothing survives between runs.
- Each result is **verified** (row counts, sum of ids for pagination). A wrong result is flagged with `(!)`.
- 1 discarded warm-up run, then the median of `--iterations` runs.

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
