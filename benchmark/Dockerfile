FROM php:8.2-cli-bookworm

# SPX profiler (function-level timings), built but NOT loaded by default so it never affects bench.php.
# Load it on demand: php -d extension=spx.so ...
ARG SPX_VERSION=v0.4.15

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip zlib1g-dev \
    && rm -rf /var/lib/apt/lists/*

# pdo_sqlite is built in the official image, pdo_mysql is needed by the paper-orm test suite (MariaDB).
RUN docker-php-ext-install pdo_mysql

RUN git clone --depth 1 --branch "${SPX_VERSION}" https://github.com/NoiseByNorthwest/php-spx.git /tmp/php-spx \
    && cd /tmp/php-spx \
    && phpize \
    && ./configure \
    && make -j"$(nproc)" \
    && make install \
    && rm -rf /tmp/php-spx

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# The repositories are mounted with the same layout as on the host (see docker-compose.yml), so the
# relative Composer path repositories ("..", "../../php-sql-mapper") keep working.
# The mounted repositories belong to another user: let git read them for the version label.
RUN git config --system --add safe.directory '*' \
    && ln -s /src/paper-orm/benchmark /app

ENV HOME=/tmp
WORKDIR /app
