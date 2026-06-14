#!/bin/sh
set -e

# Ensure the public storage symlink exists (idempotent).
php artisan storage:link || true

# Rebuild framework caches on every boot so they reflect the runtime environment
# (env vars differ from build time). Safe to run for every service.
php artisan optimize

# Only the primary app container runs migrations to avoid races between replicas.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "→ Ejecutando migraciones..."
    php artisan migrate --force
fi

exec "$@"
