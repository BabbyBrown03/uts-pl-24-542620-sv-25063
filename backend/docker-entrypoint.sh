#!/bin/sh
set -e

# File .env TIDAK pernah disimpan di dalam image (lihat .dockerignore).
# Nilai konfigurasi asli berasal dari environment variable yang diset
# oleh docker-compose. Script ini membentuk .env saat container start.
cat > /app/.env <<EOF
APP_NAME="${APP_NAME:-Admin Inventaris Barang}"
APP_ENV="${APP_ENV:-production}"
APP_KEY="${APP_KEY}"
APP_DEBUG="${APP_DEBUG:-false}"
APP_URL="${APP_URL:-http://localhost:8000}"

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID

LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION="${DB_CONNECTION:-mysql}"
DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-inventaris}"
DB_USERNAME="${DB_USERNAME:-inventaris}"
DB_PASSWORD="${DB_PASSWORD}"

SANCTUM_STATEFUL_DOMAINS="${SANCTUM_STATEFUL_DOMAINS:-localhost,localhost:5173,localhost:8080,127.0.0.1}"
SANCTUM_TOKEN_EXPIRATION="${SANCTUM_TOKEN_EXPIRATION:-}"

FRONTEND_URL="${FRONTEND_URL:-http://localhost:8080}"

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
EOF

# Pastikan storage & cache bisa ditulis.
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# Tunggu database siap lalu jalankan migration.
echo "[entrypoint] Menjalankan migration..."
php artisan migrate --force

# Seeder hanya berjalan pada container pertama untuk database yang kosong,
# sehingga data yang sudah ada di named volume tidak hilang.
if [ "${RUN_SEEDER:-true}" = "true" ]; then
  echo "[entrypoint] Menjalankan seeder..."
  php artisan db:seed --force
fi

php artisan config:clear || true
php artisan route:clear || true

echo "[entrypoint] Menjalankan FrankenPHP di port 8000..."
exec "$@"