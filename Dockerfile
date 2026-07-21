FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git unzip libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY laravel-admin/composer.json laravel-admin/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY laravel-admin/ .

RUN cp .env.render .env || true
RUN php artisan key:generate --force || true
RUN php artisan migrate --force || true
RUN php artisan db:seed --force || true
RUN php artisan storage:link --force || true
RUN php artisan config:cache || true
RUN php artisan route:cache || true
RUN php artisan view:cache || true

RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
