FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && \
    apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install zip

# Enable Apache modules
RUN a2enmod rewrite headers

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy files
COPY . .

# Fix permissions
RUN chown -R www-data:www-data /var/www/html && \
    [ -f users.json ] || touch users.json && \
    [ -f transactions.json ] || touch transactions.json && \
    [ -f error.log ] || touch error.log && \
    chmod 664 users.json transactions.json error.log

EXPOSE 80
CMD ["apache2-foreground"]