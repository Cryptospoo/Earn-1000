FROM php:8.2-apache

# 1. Install system dependencies with cleanup
RUN apt-get update && \
    apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

# 2. Install PHP extensions
RUN docker-php-ext-install zip pcntl

# 3. Install Composer (standalone version)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 4. Copy only composer files first (better caching)
COPY composer.json composer.lock ./

# 5. Install dependencies (with retry logic)
RUN composer install --no-dev --no-interaction --optimize-autoloader || \
    (rm -rf vendor && composer install --no-dev --no-interaction --optimize-autoloader)

# 6. Copy remaining files
COPY . .

# 7. Fix permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod 664 users.json transactions.json error.log

EXPOSE 80
CMD ["apache2-foreground"]
