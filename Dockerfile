# Use the official PHP image as the base image
FROM php:8.2-fpm

# Install Nginx and required dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory
WORKDIR /var/www/html

# Copy the Laravel application files to the container
COPY . .

# Set permissions for storage and cache directories
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

ENV WEBROOT /var/www/html/public

# Copy your Nginx configuration file
COPY conf/nginx/nginx-site.conf /etc/nginx/sites-available/default

# Copy your PHP-FPM configuration file
COPY conf/php-fpm.d/www.conf /usr/local/etc/php-fpm.d/www.conf

# Expose port 80 (default Nginx port)
EXPOSE 80

# Copy entrypoint script
COPY scripts/entrypoint.sh /bin/entrypoint.sh
RUN chmod +x /bin/entrypoint.sh

ENTRYPOINT ["/bin/entrypoint.sh"]
