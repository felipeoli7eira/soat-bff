#!/bin/sh

set -e

echo "Ajustando permissoes"
chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "Instalando dependencias"
if [ ! -f vendor/autoload.php ]; then
    mkdir -p vendor
    composer config --global process-timeout 0
    COMPOSER_PROCESS_TIMEOUT=1200 COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --ignore-platform-reqs --no-security-blocking || {
        echo "Falha na instalacao das dependencias"
        exit 1
    }
else
    echo "Dependencias ja instaladas"
fi

if [ ! -f .env ]; then
    cp .env.example .env
    echo "Gerando chave da aplicacao"
    composer run gen-app-key
fi

echo "Iniciando o container"
exec php-fpm
