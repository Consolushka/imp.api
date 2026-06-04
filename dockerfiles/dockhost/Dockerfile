FROM php:8.2-apache

# Объявляем ARG для переменных сборки
ARG APP_NAME
ARG APP_ENV
ARG DB_CONNECTION
ARG DB_HOST
ARG DB_PORT
ARG DB_DATABASE
ARG DB_USERNAME
ARG DB_PASSWORD
ARG LOG_LEVEL
ARG QUEUE_CONNECTION

# Преобразуем ARG в ENV для runtime
ENV APP_NAME=$APP_NAME
ENV APP_ENV=$APP_ENV
ENV APP_DEBUG=$APP_DEBUG
ENV APP_URL=$APP_URL
ENV DB_CONNECTION=$DB_CONNECTION
ENV DB_HOST=$DB_HOST
ENV DB_PORT=$DB_PORT
ENV DB_DATABASE=$DB_DATABASE
ENV DB_USERNAME=$DB_USERNAME
ENV DB_PASSWORD=$DB_PASSWORD
ENV LOG_LEVEL=$LOG_LEVEL
ENV QUEUE_CONNECTION=$QUEUE_CONNECTION

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Включаем Apache mod_rewrite
RUN a2enmod rewrite

# Настраиваем Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
COPY dockerfiles/dockhost/apache/timeout.conf /etc/apache2/conf.d/timeout.conf

# Устанавливаем рабочую директорию
WORKDIR /var/www/html

# Копируем entrypoint скрипт
COPY dockerfiles/dockhost/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Копируем кастомный php.ini
COPY dockerfiles/dockhost/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Копируем composer файлы и устанавливаем зависимости
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --optimize-autoloader --no-dev

# Копируем остальные файлы
COPY . .
RUN composer dump-autoload --optimize

# Устанавливаем права доступа
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Создаем директории
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost/ || exit 1

# Используем entrypoint скрипт
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]