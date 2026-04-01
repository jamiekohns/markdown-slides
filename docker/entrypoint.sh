#!/usr/bin/env sh
set -eu

cd /var/www/html

# Ensure public storage paths resolve after container boot.
php artisan storage:link --force >/dev/null 2>&1 || true

exec "$@"
