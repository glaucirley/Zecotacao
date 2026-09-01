FROM php:8.3-fpm-alpine

# Instala pacotes do sistema e Nginx
RUN apk add --no-cache \
    nginx git curl unzip libzip-dev libpng-dev libjpeg-turbo-dev \
    freetype-dev libxml2-dev oniguruma-dev icu-dev supervisor

# Extensões do PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mysqli mbstring zip gd xml intl bcmath opcache

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copia o código da aplicação
COPY . .

# Instala dependências de produção do Laravel
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permissões do Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Configuração do Nginx
COPY nginx.conf /etc/nginx/http.d/default.conf

EXPOSE 80

# Inicia o PHP-FPM e o Nginx juntos
CMD php-fpm -D && nginx -g "daemon off;"