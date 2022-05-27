FROM php:8-cli-alpine

WORKDIR /var/runtime

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.* /var/runtime/
RUN composer install --no-dev --no-autoloader --no-scripts --no-progress

COPY . /var/runtime/

RUN chmod -Rv 0777 /var/runtime/storage \
    && composer install --no-dev --no-progress --classmap-authoritative

ENTRYPOINT [ "php", "/var/runtime/lambda" ]
