#!/bin/bash
set -e

# Ensure data directory exists and has correct permissions
mkdir -p /var/www/html/data/emails
chown -R www-data:www-data /var/www/html/data
chmod -R 755 /var/www/html/data

# Run database migrations automatically
echo "Running database migrations..."
php -f /var/www/html/migrate.php

# Start Apache
exec apache2-foreground
