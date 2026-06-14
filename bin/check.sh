#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

APP=(docker compose exec -T app)
NODE=(docker compose exec -T vite)

GREEN='\033[0;32m'
RED='\033[0;31m'
CYAN='\033[0;36m'
BOLD='\033[1m'
RESET='\033[0m'

STEP=0
FAILED=0

ensure_stack() {
    if ! docker compose ps --status running --services 2>/dev/null | grep -qx app; then
        echo -e "${CYAN}→ Levantando contenedores Docker...${RESET}"
        docker compose up -d --build
    fi

    if [ ! -d vendor ]; then
        echo -e "${CYAN}→ Instalando dependencias PHP...${RESET}"
        docker compose exec -T app composer install --no-interaction
    fi

    if [ ! -d node_modules ]; then
        echo -e "${CYAN}→ Instalando dependencias Node...${RESET}"
        docker compose exec -T vite npm install
    fi
}

run_step() {
    local label="$1"
    shift

    STEP=$((STEP + 1))
    echo ""
    echo -e "${BOLD}${CYAN}[$STEP] $label${RESET}"

    if "$@"; then
        echo -e "${GREEN}✓ $label${RESET}"
    else
        echo -e "${RED}✗ $label${RESET}" >&2
        FAILED=$((FAILED + 1))
    fi
}

ensure_stack

echo ""
echo -e "${BOLD}Ticketera — suite de calidad${RESET}"
echo "================================"

run_step "Composer validate" "${APP[@]}" composer validate --strict --no-check-publish
run_step "Composer audit (seguridad)" "${APP[@]}" composer audit
run_step "Laravel Pint (estilo PHP)" "${APP[@]}" ./vendor/bin/pint --test
run_step "PHPStan + Larastan (análisis estático)" "${APP[@]}" ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress
run_step "Rector (refactors pendientes)" "${APP[@]}" ./vendor/bin/rector process --dry-run
run_step "Laravel — rutas compilables" bash -c "${APP[*]} php artisan config:clear && ${APP[*]} php artisan route:list --except-vendor >/dev/null"
run_step "PHPUnit (tests)" "${APP[@]}" php artisan test
run_step "npm audit (seguridad frontend)" "${NODE[@]}" npm audit --audit-level=high
run_step "ESLint (Vue/JS)" "${NODE[@]}" npm run lint
run_step "Prettier (formato frontend)" "${NODE[@]}" npm run format:check
run_step "Vite build (frontend producción)" "${NODE[@]}" npm run build

echo ""
echo "================================"
if [ "$FAILED" -eq 0 ]; then
    echo -e "${GREEN}${BOLD}Todo OK — $STEP checks pasaron.${RESET}"
    exit 0
fi

echo -e "${RED}${BOLD}$FAILED de $STEP checks fallaron.${RESET}"
exit 1
