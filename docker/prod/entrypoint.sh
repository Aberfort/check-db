#!/bin/sh
set -e

cd /var/www/html

mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
touch database/database.sqlite

php artisan config:clear
php artisan migrate --force

php artisan queue:work --sleep=1 --tries=1 --timeout=0 &

exec php artisan serve --host 0.0.0.0 --port "${PORT:-8080}" --no-reload
