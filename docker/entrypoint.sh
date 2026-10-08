#!/bin/bash
set -e

# Render などが指定するポートで待ち受ける
PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

if [ "${1}" == "apache2-foreground" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Rails 版の db:prepare 相当 (既存テーブルがある場合は何もしない)
    php artisan migrate --force

    # LINE 通知などの定期実行 (Rails 版の Solid Queue in Puma 相当)
    su -s /bin/bash www-data -c "php artisan schedule:work" &
fi

exec "$@"
