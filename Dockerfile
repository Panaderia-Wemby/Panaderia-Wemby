# ============================================================
# Stage 1: Frontend - Compila assets con Vite
# ============================================================
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm install

COPY resources ./resources
COPY public ./public
COPY vite.config.js postcss.config.js tailwind.config.js ./

# Evita que Laravel intente usar Vite dev server en runtime
RUN rm -f public/hot && npm run build

# ============================================================
# Stage 2: Builder - Instala Laravel desde 0
# ============================================================
FROM php:8.2-fpm-alpine AS builder

RUN apk add --no-cache \
    ca-certificates \
    curl \
    openssl \
    $PHPIZE_DEPS \
    libzip-dev \
    pkgconf \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    bash \
    make \
    g++

# Instalar extensiones PHP necesarias
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    bcmath \
    fileinfo \
    mbstring \
    xml \
    xmlwriter

# Instalar PECL gRPC
RUN apk add --no-cache protobuf-dev && \
    pecl install grpc && \
    docker-php-ext-enable grpc

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

# Copiar composer.json y composer.lock
COPY composer.json composer.lock* ./

# Instalar dependencias
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --optimize-autoloader

# Copiar código de la aplicación
COPY . .

# Asegura assets compilados y elimina referencia a dev server
COPY --from=frontend /app/public/build ./public/build
RUN rm -f public/hot

# Generar key de la aplicación
RUN php artisan key:generate --no-interaction || true

# Cache de configuración y rutas
RUN php artisan config:cache && \
    php artisan route:cache

# ============================================================
# Stage 3: Runtime - Imagen final optimizada
# ============================================================
FROM php:8.2-fpm-alpine

LABEL maintainer="Panaderia-Wemby SD"

RUN apk add --no-cache \
    ca-certificates \
    curl \
    openssl \
    $PHPIZE_DEPS \
    libzip \
    libzip-dev \
    pkgconf \
    oniguruma-dev \
    libxml2-dev \
    mysql-client \
    redis \
    supervisor \
    bash \
    dcron \
    protobuf

# Instalar extensiones PHP necesarias
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    bcmath \
    fileinfo \
    mbstring \
    xml \
    xmlwriter

# Instalar PECL gRPC
RUN apk add --no-cache protobuf-dev && \
    pecl install grpc && \
    docker-php-ext-enable grpc

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Crear usuario www-data con permisos
RUN addgroup -g 82 -S www-data && \
    adduser -S -D -H -u 82 -h /var/cache/www-data -s /sbin/nologin -G www-data -g www-data www-data 2>/dev/null || true

WORKDIR /var/www/html

# Copiar app desde builder
COPY --from=builder --chown=www-data:www-data /app .

# Crear directorios necesarios
RUN mkdir -p /var/www/html/storage/logs && \
    mkdir -p /var/www/html/storage/framework/cache/data && \
    mkdir -p /var/www/html/storage/framework/sessions && \
    mkdir -p /var/www/html/storage/framework/views && \
    mkdir -p /var/www/html/bootstrap/cache && \
    chown -R www-data:www-data /var/www/html && \
    mkdir -p /var/log/php && \
    chown www-data:www-data /var/log/php

# Regenerar cachés con la ruta final del contenedor
RUN php artisan config:cache && \
    php artisan route:cache

# Configurar PHP-FPM para red
RUN sed -i 's/^listen = .*$/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/;listen.backlog = /listen.backlog = /' /usr/local/etc/php-fpm.d/www.conf

# Configuración PHP adicional
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/99-custom.ini && \
    echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/99-custom.ini && \
    echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/99-custom.ini

EXPOSE 9000 50051

CMD ["php-fpm"]
