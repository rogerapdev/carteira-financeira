#!/bin/bash
set -e

# Instalar dependências do Composer se ainda não foram instaladas
if [ ! -d "vendor" ]; then
  echo "🔄 Instalando dependências do Composer..."
  composer install --no-interaction --optimize-autoloader
fi

# Verificar se as migrações precisam ser executadas
if [ "${RUN_MIGRATIONS}" = "true" ]; then
  echo "🔄 Executando migrações..."
  php artisan migrate --force
fi

# Verificar se os seeds precisam ser executados
if [ "${RUN_SEEDS}" = "true" ]; then
  echo "🔄 Executando seeds..."
  php artisan db:seed --force
fi

# Iniciar o Laravel Horizon em background se necessário
if [ "${RUN_HORIZON}" = "true" ]; then
  echo "🔄 Iniciando Laravel Horizon..."
  php artisan horizon &
fi

# Garantir permissões corretas para storage e bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Iniciar o PHP-FPM
echo "🚀 Iniciando PHP-FPM..."
php-fpm