#!/bin/bash
set -e

# Ensure data directory exists and has correct permissions
mkdir -p /var/www/html/data/emails
chown -R www-data:www-data /var/www/html/data
chmod -R 755 /var/www/html/data

# Start Apache
exec apache2-foreground
