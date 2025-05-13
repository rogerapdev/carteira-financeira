FROM php:8.1-fpm

# Instalar dependências
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    git

# Instalar extensões PHP
RUN docker-php-ext-install pdo pdo_pgsql zip pcntl
RUN pecl install redis && docker-php-ext-enable redis

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Script de inicialização
COPY docker/api/start.sh /usr/local/bin/start
RUN chmod +x /usr/local/bin/start

# Garantir permissões corretas para o diretório de documentação do Swagger
RUN mkdir -p /var/www/html/storage/api-docs/api \
    && chown -R www-data:www-data /var/www/html/storage/api-docs \
    && chmod -R 775 /var/www/html/storage/api-docs

RUN chown -R www-data:www-data /var/www/html/storage
RUN chmod -R 775 /var/www/html/storage
#RUN composer install --optimize-autoloader --no-dev

# Configurar permissões para o diretório de trabalho
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 9000
CMD ["start"]