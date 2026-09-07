FROM php:8.4-fpm-alpine as base

WORKDIR /tmp

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN sed -i s#dl-cdn.alpinelinux.org#mirror.yandex.ru/mirrors#g /etc/apk/repositories &&\
    chmod 755  /usr/local/bin/install-php-extensions  &&\
    apk add --no-cache bash \
                       tini \
                       tzdata \
                       libsasl \
                       libpq-dev \
                       busybox-suid \
                       linux-headers \
                       postgresql-client \
    && cp /usr/share/zoneinfo/Europe/Moscow /etc/localtime \
    && install-php-extensions redis pdo_pgsql rdkafka\
    && rm -rf /tmp/* /usr/local/lib/php/doc/* /var/cache/apk/* \
    && sed -i "s/82/101/g" /etc/passwd \
    && sed -i "s/82/101/g" /etc/group \
    && mkdir -p /data /var/log/php-fpm /var/log/php \
    && chown -R 101:101 /data /var/log/php-fpm /var/log/php /home/www-data \
    && chmod 777 /var/log/php-fpm

ENTRYPOINT ["/sbin/tini", "--"]


FROM base as dev

COPY configs/fpm/www.conf                          /usr/local/etc/php-fpm.d/www.conf

RUN apk add $PHPIZE_DEPS
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

WORKDIR /data
USER www-data

CMD ["php-fpm"]
