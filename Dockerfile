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

FROM base AS backend-builder

WORKDIR /build

# Copy Composer binary from official image
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Copy composer files for dependency installation
COPY composer.json composer.lock ./

# Install PHP dependencies (production only)
RUN --mount=type=cache,target=/root/.composer/cache,sharing=locked \
    composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-ansi \
    --no-progress \
    --optimize-autoloader

# Copy application source
COPY . .

# Copy built frontend assets from frontend-builder stage
COPY --from=frontend-builder /build/public/build ./public/build

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

RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose ports
# 80 = Nginx
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD ["/usr/local/bin/healthcheck.sh"]

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
