#!/bin/sh
# Prepara Laravel al arrancar el contenedor: cachés, migraciones y datos iniciales.
set -e
cd /var/www/html

# Railway (y otros PaaS) indican el puerto en $PORT; Apache escucha en él.
if [ -n "$PORT" ] && [ "$PORT" != "80" ]; then
    sed -ri "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
    sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

# Datos de demostración solo si la base está vacía (primer arranque)
if [ "${SEED_IF_EMPTY:-false}" = "true" ]; then
    php artisan transmetro:sembrar-si-vacia
fi

exec "$@"
