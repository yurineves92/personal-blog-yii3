#!/bin/sh
set -e

cd /app

mkdir -p runtime public/assets
chmod -R 0777 runtime public/assets 2>/dev/null || true

# Só faz o bootstrap da aplicação no processo principal (php-fpm),
# não em `docker compose exec/run php ./yii ...`.
if [ "$1" = "php-fpm" ]; then
    if [ ! -f vendor/autoload.php ]; then
        echo "[entrypoint] Instalando dependências do Composer..."
        composer install --no-interaction --prefer-dist --no-progress
    fi

    echo "[entrypoint] Aguardando o MySQL em ${DB_HOST}:${DB_PORT}..."
    until php -r '
        try {
            new PDO(sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT"), getenv("DB_NAME")), getenv("DB_USER"), getenv("DB_PASSWORD"));
            exit(0);
        } catch (Throwable $e) {
            exit(1);
        }'; do
        sleep 2
    done

    echo "[entrypoint] Aplicando migrations..."
    ./yii migrate:up --no-interaction

    echo "[entrypoint] Populando dados iniciais (se necessário)..."
    ./yii app:seed
fi

exec "$@"
