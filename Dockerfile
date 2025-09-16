# Use official PHP-Apache image
FROM php:8.2-apache

# Enable Apache mod_rewrite (useful for routing if needed)
RUN a2enmod rewrite

# Copy application files
WORKDIR /var/www/html

# Copy your PHP scripts into the public folder (Apache serves from here)
COPY submit.php public/
COPY download.php public/
COPY public/ ./public/

# Copy composer files if you’re using composer (optional, remove if not needed)
COPY composer.json composer.lock ./

# Install PHP dependencies with composer (if composer.json exists)
RUN apt-get update && apt-get install -y git unzip \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm composer-setup.php \
    && composer install --no-dev --optimize-autoloader || true

# Configure Apache to serve from public/
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80

# Run Apache in foreground
CMD ["apache2-foreground"]