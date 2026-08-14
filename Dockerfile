FROM php:8.4-fpm

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libpq-dev nginx

# Устанавливаем расширения PHP
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Устанавливаем Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

WORKDIR /var/www
COPY . .

# Устанавливаем зависимости (игнорируем pcntl/posix для Windows)
RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix

# Устанавливаем фронтенд
RUN npm install && npm run build

# Права на папки
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Очистка кэша
RUN php artisan optimize:clear

EXPOSE 10000
CMD php artisan serve --host=0.0.0.0 --port=10000
