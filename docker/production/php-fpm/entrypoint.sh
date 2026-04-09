#!/bin/sh
set -e

if [ ! "$(ls -A /var/www/storage)" ]; then
  echo "Initializing storage directory..."
  cp -R /var/www/storage-init/. /var/www/storage
  chown -R www-data:www-data /var/www/storage
fi

rm -rf /var/www/storage-init

php artisan optimize:clear

exec "$@"
