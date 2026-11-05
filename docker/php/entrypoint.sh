#!/bin/sh
set -e

cd /var/www/html

# 1. Dependencias de PHP (solo si no están instaladas)
if [ ! -f vendor/autoload.php ]; then
    echo ">> Instalando dependencias de Composer..."
    composer install --no-interaction --prefer-dist
fi

# 2. Archivo .env
if [ ! -f .env ]; then
    echo ">> Creando .env a partir de .env.example..."
    cp .env.example .env
fi

# 3. APP_KEY
if ! grep -q '^APP_KEY=base64:' .env; then
    echo ">> Generando APP_KEY..."
    php artisan key:generate --force
fi

# 4. Directorios de trabajo y permisos
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

# 5. Esperar a que la base de datos responda
echo ">> Esperando la base de datos en ${DB_HOST:-db}:${DB_PORT:-3306}..."
for i in $(seq 1 30); do
    if php -r "exit(@fsockopen(getenv('DB_HOST') ?: 'db', (int)(getenv('DB_PORT') ?: 3306)) ? 0 : 1);"; then
        break
    fi
    sleep 2
done

# 6. Migraciones y enlace de storage
echo ">> Ejecutando migraciones..."
php artisan migrate --force

php artisan storage:link >/dev/null 2>&1 || true

echo ">> Contenedor listo."

exec "$@"
