# Base stage with common dependencies
FROM php:8.4-cli-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    zip \
    libzip-dev \
    linux-headers \
    && docker-php-ext-install zip pcntl sockets

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies (without dev dependencies for production)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application code
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --classmap-authoritative


# Development stage
FROM base AS dev

# Install dev dependencies
RUN composer install --prefer-dist

# Keep container running for development
CMD ["tail", "-f", "/dev/null"]


# Test stage
FROM base AS test

# Install dev dependencies (needed for PHPUnit)
RUN composer install --prefer-dist

# Run tests
CMD ["./vendor/bin/phpunit"]


# Production stage
FROM base AS prod

# Expose default port
EXPOSE 8080

# Start the server
CMD ["php", "server.php", "0.0.0.0", "8080"]
