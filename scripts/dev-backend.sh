#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../backend"

php artisan serve --host=127.0.0.1 --port=8000 &
php artisan queue:work --timeout=900 --tries=1 &
php artisan schedule:work &

echo "Laravel http://127.0.0.1:8000 — queue + scheduler running. Ctrl+C stops all."
wait
