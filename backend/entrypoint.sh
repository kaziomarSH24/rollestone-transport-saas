#!/bin/sh

# Change ownership of storage and bootstrap/cache directories
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Change permissions of storage and bootstrap/cache directories
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Execute the command passed as arguments to this script (e.g., "php-fpm")
exec "$@"
