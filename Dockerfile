FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install required packages: git, zip, unzip, and other dependencies
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Copy composer files if you’re using PHP dependencies
COPY composer.json composer.lock ./
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev \
    && rm composer-setup.php

# Copy backend PHP scripts (important!)
COPY submit.php download.php ./

# Copy files folder
COPY files/ ./files/

# Copy the frontend
COPY public/ ./public/

# Change Apache document root to /public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]