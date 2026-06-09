#!/bin/sh
set -e

cd /var/www/html

PORT="${PORT:-8080}"
sed "s/@PORT@/${PORT}/g" /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf

mkdir -p storage/framework/{sessions,views,cache/data} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

php artisan storage:link --force 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force --no-interaction

php-fpm -D
exec nginx -g 'daemon off;'
