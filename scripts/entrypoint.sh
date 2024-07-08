#!/bin/sh

# Install Composer dependencies
echo "Running composer"
composer global require hirak/prestissimo
composer install --no-dev --working-dir=/var/www/html


# Cache config and routes
echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Running migrations..."
php artisan migrate --force

php artisan passport:optimized-install

chmod 777 storage/oauth-private.key
chmod 777 storage/oauth-public.key

# Start PHP-FPM in the background
php-fpm &

# Start Nginx in the foreground
nginx -g "daemon off;"
