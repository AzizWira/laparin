#!/bin/sh
set -eu

php artisan migrate --force --no-interaction

if [ "${LAPARIN_SEED_CATALOG:-false}" = "true" ]; then
    php artisan db:seed --class=CatalogSeeder --force --no-interaction
fi

exec apache2-foreground
