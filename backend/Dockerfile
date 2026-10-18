# ---------- Stage 1: instal dependency PHP ----------
# Versi image dasar eksplisit (bukan latest).
FROM composer:2.8.8 AS vendor

WORKDIR /app

# composer.json dan composer.lock disalin lebih dulu, lalu dependency diinstal,
# SEBELUM kode aplikasi disalin => cache layer tetap efektif saat kode berubah.
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist

# ---------- Stage 2: runtime ----------
FROM php:8.3-fpm-bookworm

# Ekstensi dibutuhkan Laravel + koneksi MySQL + FrankenPHP.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev libicu-dev libssl-dev libcurl4-openssl-dev unzip curl git ca-certificates \
    && docker-php-ext-install pdo_mysql zip intl \
    && apt-get install -y --no-install-recommends \
        libonig-dev libbrotli-dev libzstd-dev libargon2-dev libeditreadline-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Salin dependency hasil stage 1. File .env tidak pernah disalin ke image.
COPY --from=vendor /app/vendor ./vendor

# Salin kode aplikasi.
COPY . .

RUN php artisan config:clear || true \
    && php artisan route:clear || true \
    && chown -R www-data:www-data storage bootstrap/cache

# Nilai default. Kredensial asli SELALU berasal dari docker-compose / env,
# bukan dari Dockerfile.
ENV APP_ENV=production \
    APP_DEBUG=false \
    DB_CONNECTION=mysql \
    CACHE_STORE=file \
    SESSION_DRIVER=file \
    QUEUE_CONNECTION=sync

EXPOSE 8000

CMD ["php-fpm", "-F"]