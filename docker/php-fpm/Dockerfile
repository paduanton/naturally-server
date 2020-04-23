FROM php:7.3.6-fpm-alpine3.9
RUN apk add --no-cache openssl bash nodejs npm
RUN docker-php-ext-install bcmath pdo pdo_mysql
RUN docker-php-ext-install json tokenizer
RUN docker-php-ext-install mbstring

RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/community/ --allow-untrusted gnu-libiconv
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

WORKDIR /var/www

RUN rm -rf /var/www/html
RUN ln -s public html

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /var/www

# RUN chmod -R 775 /var/www/storage
# RUN chown -R root:www-data /var/www/storage

EXPOSE 9000

ENTRYPOINT [ "php-fpm" ]