FROM php:8.4-fpm-alpine

WORKDIR /var/www/messager

# Устанавливаем зависимости + PHP расширения
RUN apk --no-cache add \
    git \
    curl \
    unzip \
    zip \
    bash \
    shadow \
    su-exec \
    ca-certificates \
    $PHPIZE_DEPS \
    supervisor \
    && docker-php-ext-install pdo pdo_mysql \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && usermod -u 1000 www-data \
    && groupmod -g 1000 www-data

# Устанавливаем Composer
COPY --from=composer:2.8.10 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_HOME=/composer
ENV PATH="${COMPOSER_HOME}/vendor/bin:${PATH}"

RUN composer global require laravel/installer --no-interaction --prefer-dist

# Создаем директорию для Supervisor
RUN mkdir -p /etc/supervisor.d

# Копируем конфиг воркера
COPY ./configs/laravel-worker.conf /etc/supervisor.d/laravel-worker.ini

# Копируем основной конфиг supervisord
COPY ./supervisord.conf /etc/supervisord.conf

# Даем права
RUN chown -R www-data:www-data /var/www

USER www-data

CMD ["supervisord", "-c", "/etc/supervisord.conf"]
