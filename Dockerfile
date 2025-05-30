FROM php:8.1-fpm

# Sistem paketlerini yükle
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# PHP eklentilerini yükle
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Composer'ı yükle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Çalışma dizinini ayarla
WORKDIR /var/www/html

# Uygulama dosyalarını kopyala
COPY . .

# Composer bağımlılıklarını yükle
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Gerekli izinleri ayarla
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage

# PHP-FPM yapılandırmasını kopyala
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# PHP yapılandırmasını kopyala
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Uygulama için gerekli dizinleri oluştur
RUN mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/cache

# Uygulama için gerekli izinleri ayarla
RUN chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage

# Uygulama için gerekli ortam değişkenlerini ayarla
ENV APP_ENV=production
ENV APP_DEBUG=false

# Uygulama için gerekli portu aç
EXPOSE 9000

# Uygulamayı başlat
CMD ["php-fpm"] 