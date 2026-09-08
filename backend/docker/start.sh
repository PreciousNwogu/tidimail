#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ] || [ "$APP_KEY" = "base64:" ]; then
  echo "FATAL: APP_KEY is empty. On Render → Environment, add APP_KEY from: php artisan key:generate --show"
  exit 1
fi

export SESSION_DRIVER="${SESSION_DRIVER:-file}"
export CACHE_DRIVER="${CACHE_DRIVER:-file}"
export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"

php artisan config:clear
php artisan migrate --force --no-interaction

# Same box must run jobs or the scan stays on "Starting the scan…"
php artisan queue:work --sleep=1 --timeout=900 --tries=1 &
QUEUE_PID=$!

php artisan serve --host=0.0.0.0 --port="${PORT:-8000}" &
SERVE_PID=$!

trap 'kill $QUEUE_PID $SERVE_PID 2>/dev/null || true' EXIT INT TERM
wait "$SERVE_PID"
