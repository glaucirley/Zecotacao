FROM php:8.3-fpm-alpine

# Instala pacotes do sistema, Nginx e dependências
RUN apk add --no-cache \
    nginx git curl unzip libzip-dev libpng-dev libjpeg-turbo-dev \
    freetype-dev libxml2-dev oniguruma-dev icu-dev supervisor

# Extensões do PHP necessárias para o Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mysqli mbstring zip gd xml intl bcmath opcache

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Permite o Composer rodar como superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# Copia todo o código do projeto
COPY . .

# Instala dependências ignorando scripts automáticos do artisan durante o build
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Cria as pastas do Laravel caso não existam e ajusta as permissões
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copia a configuração do Nginx
COPY nginx.conf /etc/nginx/http.d/default.conf

EXPOSE 80

# Inicia o PHP-FPM e o Nginx juntos
CMD ["/bin/sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]