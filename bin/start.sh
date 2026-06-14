#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

echo "→ Levantando Ticketera (MariaDB + Redis + Nginx + PHP-FPM + Queue + Scheduler + Vite)..."
docker compose up -d --build

echo "→ Ajustando permisos de storage y bootstrap/cache..."
docker compose exec -u root -T app sh -c 'chown -R 1000:1000 storage bootstrap/cache && chmod -R ug+rwx storage bootstrap/cache'

if [ ! -d vendor ]; then
  echo "→ Instalando dependencias PHP (composer install)..."
  docker compose exec app composer install --no-interaction
fi

if [ ! -f .env ]; then
  echo "→ Creando .env desde .env.example..."
  cp .env.example .env
  docker compose exec app php artisan key:generate
fi

echo ""
echo "   App:   http://127.0.0.1:8000"
echo "   Vite:  http://localhost:5173"
echo "   Redis: localhost:6379"
echo ""
echo "   Cursor: Ctrl+Shift+P → Simple Browser: Show → http://127.0.0.1:8000"
echo ""
echo "   Artisan: ./bin/artisan migrate --seed"
echo "   Logs:    docker compose logs -f"
echo "   Stop:    docker compose down"
