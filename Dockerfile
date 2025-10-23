# Folosim imaginea oficială PHP 8.3 FPM Alpine (lightweight)
FROM php:8.3-fpm-alpine

# Setează directorul de lucru
WORKDIR /var/www/html

# Instalează dependențe sistem necesare pentru extensiile PHP
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    nginx \
    supervisor

# Instalează extensiile PHP necesare (inclusiv bcmath!)
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    xml \
    bcmath \
    gd \
    opcache

# Copiază fișierele Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiază fișierele aplicației
COPY . .

# Instalează dependențele PHP (fără dev dependencies pentru producție)
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Setează permisiuni pentru directoarele Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expune portul 8080 (Railway folosește acest port implicit)
EXPOSE 8080

# Comandă de start
CMD php artisan serve --host=0.0.0.0 --port=8080
