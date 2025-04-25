#!/bin/sh
set -e

# Create necessary directories
mkdir -p /var/www/var/cache /var/www/var/log

# Set proper permissions
chown -R www-data:www-data /var/www/var
chmod -R 777 /var/www/var/cache /var/www/var/log

# Create exchange rates log file if it doesn't exist
touch /var/www/var/log/exchange_rates.log
chmod 666 /var/www/var/log/exchange_rates.log

# First arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

exec "$@"
