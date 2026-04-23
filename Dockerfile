FROM registry.dev.hugyourcustomer.ai/huglabs/php8.2:latest AS backend-builder

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

FROM registry.dev.hugyourcustomer.ai/huglabs/php8.2:latest AS production

ARG USER_ID=1000
ARG GROUP_ID=1000

WORKDIR /app

# Copy application from backend builder
COPY --from=backend-builder --chown=www-data:www-data /build ./

# Copy configuration files
COPY docker/php/php.ini /etc/php/8.2/cli/php.ini
COPY docker/nginx/laravel.conf /etc/nginx/sites-available/default
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/healthcheck.sh /usr/local/bin/healthcheck.sh

RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/healthcheck.sh

# Expose ports
# 80 = Nginx
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD ["/usr/local/bin/healthcheck.sh"]

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
