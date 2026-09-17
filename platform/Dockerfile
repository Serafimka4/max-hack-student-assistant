# syntax=docker/dockerfile:1
FROM dunglas/frankenphp:1-php8.5-bookworm

RUN install-php-extensions pdo_pgsql intl zip bcmath gd opcache pcntl @composer

# Доверенные корневые сертификаты (например, Минцифры для platform-api2.max.ru): положите *.crt в docker/certs.
COPY docker/certs/ /usr/local/share/ca-certificates/extra/
RUN update-ca-certificates

ENV SERVER_NAME=:80 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    PHP_INI_SCAN_DIR=":/usr/local/etc/php/app.d"

WORKDIR /app

COPY docker/php.ini /usr/local/etc/php/app.d/app.ini
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && php artisan filament:assets \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 80
ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
