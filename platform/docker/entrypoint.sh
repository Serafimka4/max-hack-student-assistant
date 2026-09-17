#!/bin/sh
set -e

# Ключ приложения: из окружения или сохранённый в томе storage при первом запуске.
if [ -z "$APP_KEY" ]; then
  KEY_FILE=/app/storage/app/.app_key
  if [ ! -s "$KEY_FILE" ]; then
    mkdir -p "$(dirname "$KEY_FILE")"
    php artisan key:generate --show > "$KEY_FILE"
  fi
  APP_KEY="$(cat "$KEY_FILE")"
  export APP_KEY
fi

mkdir -p storage/app/private storage/framework/cache storage/framework/sessions storage/framework/views storage/logs

if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
  php artisan migrate --force
  if [ "${SEED_DEMO:-0}" = "1" ] && [ "$(php artisan tinker --execute='echo \App\Models\Organization::count();')" = "0" ]; then
    php artisan db:seed --force
  fi
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
