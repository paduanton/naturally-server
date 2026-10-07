# Development/test tools only; application source is mounted when needed.
FROM php:8.5-cli-bookworm@sha256:819080ed413631f6ec564184dd9acd08d9425bd863b9f8b5876b5ea2147d55e7 AS development

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libonig-dev libxml2-dev libzip-dev libpng-dev libicu-dev libjpeg62-turbo-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j2 pdo_mysql mbstring zip gd intl bcmath \
    && pecl install xdebug-3.5.3 \
    && pecl install redis-6.3.0 \
    && docker-php-ext-enable xdebug redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
ENV XDEBUG_MODE=off COMPOSER_HOME=/tmp/composer
USER www-data
CMD ["php", "--version"]
