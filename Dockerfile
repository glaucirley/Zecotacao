FROM php:8.3-fpm-alpine

# Instala pacotes essenciais do sistema, Nginx e utilitários
RUN apk add --no-cache \
    nginx git curl unzip libzip-dev libpng-dev libjpeg-turbo-dev \
    freetype-dev libxml2-dev oniguruma-dev icu-dev supervisor libaio libnsl libc6-compat

# Baixa o gerenciador oficial de extensões PHP (mlocati) que gerencia o Oracle Instant Client automaticamente
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Instala as extensões tradicionais + OCI8 e PDO_OCI
RUN install-php-extensions \
    pdo_mysql \
    mysqli \
    mbstring \
    zip \
    gd \
    xml \
    intl \
    bcmath \
    opcache \
    oci8 \
    pdo_oci

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# Copia todo o código da aplicação
COPY . .

# Instala dependências de produção do Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Garante permissões do Laravel
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Configuração do Nginx
COPY nginx.conf /etc/nginx/http.d/default.conf

EXPOSE 80

CMD ["/bin/sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]