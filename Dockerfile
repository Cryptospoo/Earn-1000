FROM php:8.2-apache

# 1. Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install zip

# 2. Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer

# 3. Copy only composer.json first
COPY composer.json .

# 4. Install dependencies (no lockfile needed)
RUN composer install --no-dev --no-interaction --optimize-autoloader

# 5. Copy remaining files
COPY . .

# 6. Fix permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod 660 users.json transactions.json error.log

EXPOSE 80
CMD ["apache2-foreground"]
