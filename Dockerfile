FROM php:8.4-fpm

# Устанавливаем системные зависимости + Nginx
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libpq-dev nginx

# Устанавливаем расширения PHP
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Устанавливаем Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

WORKDIR /var/www/html
COPY . .

# Устанавливаем зависимости (продакшен)
RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix
RUN npm install && npm run build

# Права на папки
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# ===== ГЕНЕРАЦИЯ КОНФИГА NGINX =====
RUN echo 'server { \
    listen 8080; \
    root /var/www/html/public; \
    index index.php index.html; \
    add_header X-Frame-Options "SAMEORIGIN"; \
    add_header X-Content-Type-Options "nosniff"; \
    charset utf-8; \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ { \
        expires max; \
        log_not_found off; \
        access_log off; \
        try_files $uri =404; \
    } \
    location = /favicon.ico { access_log off; log_not_found off; } \
    location = /robots.txt  { access_log off; log_not_found off; } \
    error_page 404 /index.php; \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_param SCRIPT_FILENAME /var/www/html/public$fastcgi_script_name; \
        include fastcgi_params; \
        fastcgi_hide_header X-Powered-By; \
    } \
    location ~ /\.(?!well-known).* { \
        deny all; \
    } \
}' > /etc/nginx/sites-available/default

EXPOSE 8080

# ===== ЗАПУСК =====
CMD php artisan migrate --force --no-interaction && \
    php artisan storage:link && \
    php-fpm -F & \
    nginx -g "daemon off;"
