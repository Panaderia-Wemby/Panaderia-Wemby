#!/bin/bash

################################################################################
# Script de Deploy Rápido para Docker
#
# Uso:
#   ./deploy-docker.sh build    # Construir imagen
#   ./deploy-docker.sh up       # Levantar servicios
#   ./deploy-docker.sh down     # Detener servicios
#   ./deploy-docker.sh migrate  # Correr migraciones
#   ./deploy-docker.sh fresh    # Reset completo
#
################################################################################

set -e

COMPOSE_FILE="docker-compose.yml"
COMPOSE_CMD="docker compose -f $COMPOSE_FILE"

# Colores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() {
    echo -e "${BLUE}[*]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

build() {
    log_info "Construyendo imagen Docker..."
    $COMPOSE_CMD build --no-cache
    log_success "Imagen construida"
}

up() {
    log_info "Levantando servicios..."
    $COMPOSE_CMD up -d
    sleep 5
    log_success "Servicios iniciados"
    log_info "Estado:"
    $COMPOSE_CMD ps
}

down() {
    log_info "Deteniendo servicios..."
    $COMPOSE_CMD down
    log_success "Servicios detenidos"
}

migrate() {
    log_info "Corriendo migraciones..."
    $COMPOSE_CMD exec app1 php artisan migrate --force
    log_success "Migraciones completadas"
}

seed() {
    log_info "Ejecutando seeders..."
    $COMPOSE_CMD exec app1 php artisan db:seed
    log_success "Seeders ejecutados"
}

fresh() {
    log_info "Reset completo..."
    down
    $COMPOSE_CMD down -v --remove-orphans
    log_info "Volúmenes eliminados, levantando nuevamente..."
    up
    migrate
    seed
    log_success "Setup fresco completado"
}

logs() {
    $COMPOSE_CMD logs -f "$@"
}

shell_app() {
    log_info "Abriendo shell en app1..."
    $COMPOSE_CMD exec app1 sh
}

shell_db() {
    log_info "Abriendo shell en MySQL..."
    $COMPOSE_CMD exec mysql mysql -u sdapp -psdapppass sd_app
}

tinker() {
    log_info "Abriendo Tinker REPL..."
    $COMPOSE_CMD exec app1 php artisan tinker
}

health() {
    log_info "Estado de servicios:"
    echo ""

    # PHP-FPM
    $COMPOSE_CMD exec app1 php artisan about --no-ansi > /dev/null 2>&1 && echo -e "${GREEN}✓ Nodo 1 OK${NC}" || echo -e "${YELLOW}✗ Nodo 1 error${NC}"
    $COMPOSE_CMD exec app2 php artisan about --no-ansi > /dev/null 2>&1 && echo -e "${GREEN}✓ Nodo 2 OK${NC}" || echo -e "${YELLOW}✗ Nodo 2 error${NC}"
    $COMPOSE_CMD exec app3 php artisan about --no-ansi > /dev/null 2>&1 && echo -e "${GREEN}✓ Nodo 3 OK${NC}" || echo -e "${YELLOW}✗ Nodo 3 error${NC}"

    # MySQL
    $COMPOSE_CMD exec mysql mysqladmin ping -h localhost > /dev/null 2>&1 && echo -e "${GREEN}✓ MySQL OK${NC}" || echo -e "${YELLOW}✗ MySQL error${NC}"

    # Redis
    $COMPOSE_CMD exec redis redis-cli -a redispass ping > /dev/null 2>&1 && echo -e "${GREEN}✓ Redis OK${NC}" || echo -e "${YELLOW}✗ Redis error${NC}"

    # Nginx
    $COMPOSE_CMD exec nginx wget -q -O- http://localhost/health > /dev/null 2>&1 && echo -e "${GREEN}✓ Nginx OK${NC}" || echo -e "${YELLOW}✗ Nginx error${NC}"

    echo ""
}

print_usage() {
    cat << EOF
Uso: $0 <comando>

Comandos:
    build       Construir imagen Docker
    up          Levantar servicios
    down        Detener servicios
    migrate     Correr migraciones
    seed        Ejecutar seeders
    fresh       Reset completo (down + up + migrate + seed)
    logs        Ver logs (use: logs <servicio>)
    shell-app   Abrir shell en el contenedor de app
    shell-db    Acceder a MySQL
    tinker      Abrir PHP Tinker REPL
    health      Verificar salud de servicios

Ejemplos:
    $0 build
    $0 up
    $0 logs app1
    $0 shell-app
    $0 migrate
EOF
}

case "${1:-help}" in
    build)
        build
        ;;
    up)
        up
        ;;
    down)
        down
        ;;
    migrate)
        migrate
        ;;
    seed)
        seed
        ;;
    fresh)
        fresh
        ;;
    logs)
        logs "${2}"
        ;;
    shell-app)
        shell_app
        ;;
    shell-db)
        shell_db
        ;;
    tinker)
        tinker
        ;;
    health)
        health
        ;;
    help|*)
        print_usage
        ;;
esac
