# ────────────────────────────────────────────────────────────
# Stage 1: deps — only rebuilds when composer.lock changes
# ────────────────────────────────────────────────────────────
FROM php:8.4-fpm AS deps

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl unzip git \
    libpq-dev libonig-dev libssl-dev libxml2-dev libcurl4-openssl-dev \
    libicu-dev libzip-dev libjpeg62-turbo-dev libpng-dev libfreetype6-dev \
    zlib1g-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql pdo_pgsql pgsql opcache intl zip bcmath soap gd exif \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www

# Copy only dependency files first — layer is cached until composer.lock changes
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --no-scripts \
    --no-autoloader

# Copy full app then regenerate optimised autoloader
COPY . .

RUN composer dump-autoload --no-dev --optimize --no-scripts


# ────────────────────────────────────────────────────────────
# Stage 2: production — only rebuilds when app code changes
# ────────────────────────────────────────────────────────────
FROM php:8.4-fpm AS production

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libicu-dev libzip-dev libfcgi-bin procps \
    libjpeg62-turbo libpng16-16 libfreetype6 zlib1g ffmpeg \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN curl -fsSL -o /usr/local/bin/php-fpm-healthcheck \
    https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

COPY ./docker/production/php-fpm/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copy extensions from deps stage
COPY --from=deps /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=deps /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=deps /usr/local/bin/docker-php-ext-* /usr/local/bin/

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Copy built app from deps stage
COPY --from=deps /var/www /var/www

# Create required directories not tracked by git
RUN mkdir -p /var/www/bootstrap/cache \
             /var/www/storage/app/public \
             /var/www/storage/framework/cache/data \
             /var/www/storage/framework/sessions \
             /var/www/storage/framework/testing \
             /var/www/storage/framework/views \
             /var/www/storage/logs

WORKDIR /var/www

RUN chown -R www-data:www-data /var/www

USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
EXPOSE 9000
CMD ["php-fpm"]
