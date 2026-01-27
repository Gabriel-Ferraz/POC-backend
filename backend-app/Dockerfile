FROM ubuntu:22.04 AS base

ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies
RUN apt-get update && apt-get install -y \
    tzdata \
    software-properties-common \
    lsb-release \
    ca-certificates \
    apt-transport-https \
    curl \
    unzip \
    zip \
    build-essential \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    gnupg \
    openssl \
    && ln -fs /usr/share/zoneinfo/America/Sao_Paulo /etc/localtime \
    && dpkg-reconfigure -f noninteractive tzdata \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install PHP 8.2 + Required extensions
RUN add-apt-repository ppa:ondrej/php -y \
    && apt-get update \
    && apt-get install -y \
    php8.2 \
    php8.2-cli \
    php8.2-common \
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

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

FROM base AS frontend-builder

WORKDIR /build

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Copy package files for dependency installation
COPY package*.json ./

# Install node dependencies
RUN npm ci

# Copy frontend source files
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
COPY tailwind.config.js* ./
COPY postcss.config.js* ./
COPY tsconfig.json* ./

# Build frontend assets
RUN npm run build

FROM base AS backend-builder

WORKDIR /build

# Copy composer files for dependency installation
COPY composer.json composer.lock ./

# Install PHP dependencies (production only)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    && composer clear-cache \
    && rm -rf /root/.composer/cache

# Copy application source
COPY . .

# Copy built frontend assets from frontend-builder stage
COPY --from=frontend-builder /build/public/build ./public/build

# Run composer scripts (post-autoload-dump, etc.)
RUN composer run-script post-autoload-dump

# Generate Laravel optimizations
RUN php artisan event:cache \
    && php artisan route:cache

FROM ubuntu:22.04 AS production

ENV DEBIAN_FRONTEND=noninteractive

WORKDIR /app

# Install runtime dependencies only
RUN apt-get update && apt-get install -y \
    tzdata \
    ca-certificates \
    curl \
    supervisor \
    nginx \
    software-properties-common \
    && ln -fs /usr/share/zoneinfo/America/Sao_Paulo /etc/localtime \
    && dpkg-reconfigure -f noninteractive tzdata \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install PHP 8.2 runtime (no dev packages)
RUN add-apt-repository ppa:ondrej/php -y \
    && apt-get update \
    && apt-get install -y \
    php8.2 \
    php8.2-cli \
    php8.2-common \
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

# Copy application from backend builder
COPY --from=backend-builder --chown=www-data:www-data /build ./

# Copy configuration files
COPY docker/php/php.ini /etc/php/8.2/cli/php.ini
COPY docker/nginx/laravel.conf /etc/nginx/sites-available/default
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Set proper permissions
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose ports
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
