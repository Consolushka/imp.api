#!/bin/bash

# Создаем .env файл из переменных окружения
cat > /var/www/html/.env << EOF
APP_NAME="${APP_NAME:-Laravel}"
APP_ENV="${APP_ENV:-production}"
APP_DEBUG="${APP_DEBUG:-false}"
APP_URL="${APP_URL:-http://localhost}"

DB_CONNECTION="${DB_CONNECTION:-pgsql}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE:-basketball_imp}"
DB_USERNAME="${DB_USERNAME:-postgres}"
DB_PASSWORD="${DB_PASSWORD:-}"

LOG_LEVEL="${LOG_LEVEL:-info}"
QUEUE_CONNECTION="${QUEUE_CONNECTION:-database}"
SESSION_DRIVER=file
CACHE_DRIVER=file
EOF

# Генерируем ключ приложения если его нет
if ! grep -q "APP_KEY=" .env || [ -z "$(grep APP_KEY= .env | cut -d= -f2)" ]; then
    php artisan key:generate --force
fi

# Кешируем конфигурацию
php artisan config:cache
php artisan route:cache

# Запускаем фоновый воркер очередей
nohup php artisan queue:work --tries=3 --timeout=90 > /var/www/html/storage/logs/worker.log 2>&1 &

# Запускаем Apache
exec apache2-foreground
