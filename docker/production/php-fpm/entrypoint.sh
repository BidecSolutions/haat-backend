#!/bin/sh
set -e

mkdir -p /var/www/storage/app/public \
         /var/www/storage/framework/cache/data \
         /var/www/storage/framework/sessions \
         /var/www/storage/framework/testing \
         /var/www/storage/framework/views \
         /var/www/storage/logs

php artisan package:discover --ansi
php artisan migrate --force || echo "[warn] migrate had errors - continuing"
php artisan optimize:clear

exec "$@"
