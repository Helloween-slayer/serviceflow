FROM php:8.4-fpm

# Устанавливаем зависимости
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libpq-dev

# Устанавливаем расширения PHP
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Устанавливаем Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

WORKDIR /var/www
COPY . .

# Устанавливаем зависимости
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# Права на папки
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
RUN php artisan migrate --force
RUN php artisan optimize:clear
RUN php artisan migrate --force

EXPOSE 10000
CMD php artisan serve --host=0.0.0.0 --port=10000
