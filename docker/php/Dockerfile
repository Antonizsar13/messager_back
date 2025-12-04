FROM php:8.4-fpm-alpine

WORKDIR /var/www/messager

# Устанавливаем зависимости и расширения PHP + Node.js + npm
RUN apk --no-cache add \
    git \
    curl \
    unzip \
    zip \
    shadow \
    bash \
    su-exec \
    ca-certificates \
    nodejs \
    npm \
    $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_mysql \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && usermod -u 1000 www-data \
    && groupmod -g 1000 www-data

# Устанавливаем Composer
COPY --from=composer:2.8.10 /usr/bin/composer /usr/bin/composer

# Настраиваем общий COMPOSER_HOME и PATH
ENV COMPOSER_HOME=/composer
ENV PATH="${COMPOSER_HOME}/vendor/bin:${PATH}"

# Ставим Laravel Installer глобально (доступен по команде "laravel")
RUN composer global require laravel/installer --no-interaction --prefer-dist

# Даем права на рабочую папку
RUN chown -R www-data:www-data /var/www

USER www-data
