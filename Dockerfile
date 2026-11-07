# check=error=true

# Every base image is pinned by tag *and* digest, so a re-tagged or tampered
# upstream image is never pulled silently. Dependabot bumps both together.
FROM composer:2.11.1@sha256:8de2184c6de224f4689e8f80ed5f5edaf0030b6b8b7839a22a2bcb6722ac87d0 AS composer

FROM php:8.5.12-fpm-alpine3.24@sha256:64535609509e0fd5c8529359e6b599a68ce42089defd565f7881efdae7687cea AS php
RUN set -eux; \
    apk add --no-cache libjpeg-turbo libpng libwebp; \
    apk add --no-cache --virtual .build-deps libjpeg-turbo-dev libpng-dev libwebp-dev linux-headers; \
    docker-php-ext-configure gd --with-jpeg --with-webp; \
    docker-php-ext-install -j"$(nproc)" exif gd sockets; \
    apk del --no-network .build-deps; \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
WORKDIR /usr/share/nginx/html

# Dependencies come from composer.lock only. Plugins and scripts are disabled,
# so no package code is executed while installing.
FROM php AS vendor
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-plugins --no-scripts --no-autoloader
COPY src/ src/
RUN composer dump-autoload --no-dev --no-plugins --no-scripts --classmap-authoritative

# CI image: dev dependencies (PHPStan) plus the full source tree
FROM vendor AS dev
RUN composer install --no-interaction --no-progress --no-plugins --no-scripts
COPY . .

# PHP-FPM application image, runs unprivileged with read-only code
FROM php AS app
COPY .docker/php/up.ini "$PHP_INI_DIR/conf.d/zz-up.ini"
COPY --from=vendor /usr/share/nginx/html/vendor/ vendor/
COPY index.php upload.php config.php lang.php ./
COPY css/ css/
COPY js/ js/
COPY lang/ lang/
COPY src/ src/
COPY tpl/ tpl/
# FPM starts as www-data, so the pool's user/group switch is dropped
RUN install -d -o www-data -g www-data -m 0755 i logs \
    && sed -i '/^\(user\|group\) = /d' /usr/local/etc/php-fpm.d/www.conf
USER www-data

# nginx image serving the static assets and proxying PHP requests to the app
FROM nginx:1.31.8-alpine3.24@sha256:713fc2ebfd92e8217db451477182ee91944b123d2b1ee9fd5a8c6e91ca6c3a11 AS web
COPY .docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY .docker/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf
WORKDIR /usr/share/nginx/html
# uid/gid 82 is www-data in the app image, which owns the shared uploads volume
RUN rm -f index.html 50x.html && install -d -o 82 -g 82 -m 0755 i
COPY index.php favicon.ico manifest.json robots.txt ./
COPY css/ css/
COPY img/ img/
COPY js/ js/
COPY --from=vendor /usr/share/nginx/html/vendor/components/jquery/jquery.min.js vendor/components/jquery/
