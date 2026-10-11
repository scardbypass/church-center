#!/usr/bin/env bash
set -Eeuo pipefail
# Run from a fresh clone. This installer creates a Laravel app under apps/web.
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"
command -v php >/dev/null || { echo "PHP is required"; exit 1; }
command -v composer >/dev/null || { echo "Composer is required"; exit 1; }
command -v node >/dev/null || { echo "Node.js is required"; exit 1; }
command -v npm >/dev/null || { echo "npm is required"; exit 1; }
if [[ ! -f apps/web/artisan ]]; then
  mkdir -p apps
  composer create-project laravel/laravel apps/web "^12.0" --no-interaction
fi
cp -R overlay/web/. apps/web/
cd apps/web
composer require laravel/socialite:^5.0 --no-interaction
cd ../..
cd apps/web
if [[ ! -f .env ]]; then cp .env.example .env; fi
if ! grep -Eq "^APP_KEY=base64:.+" .env; then php artisan key:generate --force; fi
# SQLite is suitable for an initial single-server installation; configure MariaDB for production.
touch database/database.sqlite
php -r '$p=".env";$s=file_get_contents($p);$s=preg_replace("/^DB_CONNECTION=.*/m","DB_CONNECTION=sqlite",$s);file_put_contents($p,$s);'
php artisan migrate --force
npm install
npm run build
echo
echo "Installed Church Center starter. Run: cd apps/web && php artisan serve --host=127.0.0.1 --port=8088"
echo "Production: configure Nginx to serve apps/web/public and use PHP-FPM. See README.md."
