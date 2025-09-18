FROM php:8.2-fpm

# System deps
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libonig-dev libzip-dev libicu-dev libpq-dev curl \
    && docker-php-ext-install pdo_mysql mbstring zip intl gd pcntl \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Opcache (optional)
RUN docker-php-ext-enable opcache || true

WORKDIR /var/www/html

# Permissions (optional)
RUN usermod -u 1000 www-data || true && groupmod -g 1000 www-data || true
