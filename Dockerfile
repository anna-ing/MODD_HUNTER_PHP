FROM php:8.3-fpm-alpine

# Dependencias del sistema necesarias para compilar las extensiones de PHP
RUN apk add --no-cache \
        bash \
        curl \
        git \
        unzip \
        icu-dev \
        libzip-dev \
        libpng-dev \
        oniguruma-dev \
        mariadb-client

# Extensiones de PHP requeridas por Laravel y por este proyecto
RUN docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        bcmath \
        pcntl \
        zip \
        gd \
        intl \
        opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
# sed elimina posibles retornos de carro (CRLF) para evitar "exec format error"
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
