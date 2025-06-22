FROM php:8.2-apache

# 1. Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libcurl4-openssl-dev \
    git \
    && rm -rf /var/lib/apt/lists/*

# 2. Install PHP extensions
RUN docker-php-ext-install zip curl

# 3. Configure Apache
RUN a2enmod rewrite

# 4. Install Composer (with retry logic)
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer \
    --version=2.6.5

# 5. Copy only necessary files first
COPY composer.json composer.lock ./

# 6. Install dependencies (with cache)
RUN composer install --no-dev --no-interaction --optimize-autoloader

# 7. Copy remaining files
COPY . .

# 8. Fix permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod 660 users.json transactions.json error.log

EXPOSE 80
CMD ["apache2-foreground"]
