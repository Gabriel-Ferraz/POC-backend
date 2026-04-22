# syntax=docker/dockerfile:1.4
FROM ubuntu:22.04 AS base

ENV DEBIAN_FRONTEND=noninteractive

# System deps + PHP 8.2 PPA in one pass with BuildKit cache
RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    apt-get update && apt-get install -y --no-install-recommends \
    tzdata \
    software-properties-common \
    ca-certificates \
    curl \
    unzip \
    gnupg \
    openssl \
    ffmpeg \
    && ln -fs /usr/share/zoneinfo/America/Sao_Paulo /etc/localtime \
    && dpkg-reconfigure -f noninteractive tzdata \
    && add-apt-repository ppa:ondrej/php -y \
    && apt-get update && apt-get install -y --no-install-recommends \
    php8.2-cli \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-zip \
    php8.2-curl \
    php8.2-bcmath \
    php8.2-gd \
    php8.2-pgsql \
    php8.2-opcache \
    php8.2-swoole \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

FROM composer:2 AS composer

FROM base AS backend-builder

WORKDIR /build

# Copy Composer binary from previous stage (mais rápido que COPY --from=composer:2)
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

# Copy composer files first (cache layer se não mudarem)
COPY composer.json composer.lock ./

# Install PHP dependencies with cache mount
RUN --mount=type=cache,target=/root/.composer/cache,sharing=locked \
    composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-ansi \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

# Copy application source (só invalida cache se código mudar)
COPY . .

# Run post-install scripts
RUN composer run-script post-autoload-dump

FROM base AS production

ARG USER_ID=1000
ARG GROUP_ID=1000

WORKDIR /app

# Install production services
RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    apt-get update && apt-get install -y --no-install-recommends \
    supervisor \
    nginx

# Copy application from backend builder
COPY --from=backend-builder --chown=www-data:www-data /build ./

# Copy configuration files
COPY docker/php/php.ini /etc/php/8.2/cli/php.ini
COPY docker/nginx/laravel.conf /etc/nginx/sites-available/default
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/healthcheck.sh /usr/local/bin/healthcheck.sh

RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/healthcheck.sh

# Create storage directories and set permissions
RUN mkdir -p storage/app/tts storage/app/temp storage/logs storage/framework/cache storage/framework/sessions storage/framework/views storage/octane \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && touch storage/octane/.gitkeep

# Expose ports
# 80 = Nginx
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD ["/usr/local/bin/healthcheck.sh"]

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
