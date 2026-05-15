#!/bin/bash

################################################################################
# Script de Instalación Automatizado - Sistema Distribuido Panadería Wemby
# 
# Uso:
#   sudo ./setup-sd.sh --node 1 --ip 192.168.1.11 --gateway 192.168.1.1
#   sudo ./setup-sd.sh --node 2 --ip 192.168.1.12 --gateway 192.168.1.1
#   sudo ./setup-sd.sh --node 3 --ip 192.168.1.13 --gateway 192.168.1.1
#
# Adaptado de: guia_sd_proxmox.md
################################################################################

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Variables por defecto
NODE_ID=""
NODE_IP=""
GATEWAY_IP="192.168.1.1"
NETWORK_INTERFACE=""
MYSQL_PASSWORD="sdapppass"
REDIS_PASSWORD="redispass"
APP_PATH="/var/www/laravel"
PHP_VERSION="8.2"

# Logs
LOG_FILE="/var/log/sd-setup.log"

# Funciones de utilidad
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$LOG_FILE"
}

# Verificar si es root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        log_error "Este script debe ejecutarse como root (sudo)"
        exit 1
    fi
}

# Parsear argumentos
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --node)
                NODE_ID="$2"
                shift 2
                ;;
            --ip)
                NODE_IP="$2"
                shift 2
                ;;
            --gateway)
                GATEWAY_IP="$2"
                shift 2
                ;;
            --interface)
                NETWORK_INTERFACE="$2"
                shift 2
                ;;
            *)
                log_error "Opción desconocida: $1"
                print_usage
                exit 1
                ;;
        esac
    done

    # Validar argumentos requeridos
    if [ -z "$NODE_ID" ] || [ -z "$NODE_IP" ]; then
        log_error "Argumentos faltantes"
        print_usage
        exit 1
    fi

    # Auto-detectar interfaz de red si no se especifica
    if [ -z "$NETWORK_INTERFACE" ]; then
        NETWORK_INTERFACE=$(ip route | grep default | awk '{print $5}' | head -1)
        if [ -z "$NETWORK_INTERFACE" ]; then
            log_error "No se pudo detectar la interfaz de red. Use --interface"
            exit 1
        fi
    fi
}

print_usage() {
    cat << EOF
Uso: sudo $0 --node <N> --ip <IP> [--gateway <IP>] [--interface <IFACE>]

Opciones:
    --node <N>              Número del nodo (1, 2 o 3)
    --ip <IP>               IP estática del nodo (ej: 192.168.1.11)
    --gateway <IP>          IP del gateway (default: 192.168.1.1)
    --interface <IFACE>     Nombre de interfaz (default: auto-detecta)

Ejemplos:
    sudo $0 --node 1 --ip 192.168.1.11
    sudo $0 --node 2 --ip 192.168.1.12 --interface ens18
    sudo $0 --node 3 --ip 192.168.1.13 --gateway 10.0.0.1
EOF
}

# ============================================================
# FASE 1: Actualizar sistema
# ============================================================
update_system() {
    log_info "=== FASE 1: Actualizando sistema ==="
    
    apt update
    apt upgrade -y
    
    log_success "Sistema actualizado"
}

# ============================================================
# FASE 2: Configurar IP estática
# ============================================================
configure_network() {
    log_info "=== FASE 2: Configurando red estática ==="
    
    local netplan_file="/etc/netplan/00-installer-config.yaml"
    local nameserver="8.8.8.8 1.1.1.1"
    
    cat > "$netplan_file" << EOF
network:
  version: 2
  ethernets:
    $NETWORK_INTERFACE:
      dhcp4: no
      addresses:
        - $NODE_IP/24
      gateway4: $GATEWAY_IP
      nameservers:
        addresses: [$nameserver]
EOF

    netplan apply
    sleep 2
    
    # Verificar conectividad
    if ping -c 1 $GATEWAY_IP > /dev/null 2>&1; then
        log_success "Configuración de red completada: $NODE_IP"
    else
        log_error "No hay conectividad con gateway $GATEWAY_IP"
        exit 1
    fi
}

# ============================================================
# FASE 3: Instalar dependencias base
# ============================================================
install_dependencies() {
    log_info "=== FASE 3: Instalando dependencias ==="
    
    apt install -y \
        nginx \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-gd \
        mysql-client \
        redis-server \
        keepalived \
        chrony \
        supervisor \
        git \
        curl \
        unzip \
        htop \
        net-tools \
        wget
    
    log_success "Dependencias instaladas"
}

# ============================================================
# FASE 4: Instalar Composer
# ============================================================
install_composer() {
    log_info "=== FASE 4: Instalando Composer ==="
    
    if ! command -v composer &> /dev/null; then
        curl -sS https://getcomposer.org/installer | php
        mv composer.phar /usr/local/bin/composer
        chmod +x /usr/local/bin/composer
    fi
    
    log_success "Composer instalado: $(composer --version)"
}

# ============================================================
# FASE 5: Preparar directorio de la app
# ============================================================
prepare_app_directory() {
    log_info "=== FASE 5: Preparando directorio de la app ==="
    
    mkdir -p "$APP_PATH"
    chown -R www-data:www-data "$APP_PATH"
    chmod -R 755 "$APP_PATH"
    
    log_success "Directorio preparado: $APP_PATH"
}

# ============================================================
# FASE 6: Configurar PHP-FPM
# ============================================================
configure_php_fpm() {
    log_info "=== FASE 6: Configurando PHP-FPM ==="
    
    local php_conf="/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"
    
    # Escuchar en red (para balance entre nodos)
    sed -i 's/^listen = .*$/listen = 0.0.0.0:9000/' "$php_conf"
    
    # Optimizaciones
    sed -i 's/^pm\.max_children =.*/pm.max_children = 20/' "$php_conf"
    sed -i 's/^pm\.start_servers =.*/pm.start_servers = 5/' "$php_conf"
    sed -i 's/^pm\.min_spare_servers =.*/pm.min_spare_servers = 3/' "$php_conf"
    sed -i 's/^pm\.max_spare_servers =.*/pm.max_spare_servers = 10/' "$php_conf"
    
    systemctl restart php${PHP_VERSION}-fpm
    systemctl enable php${PHP_VERSION}-fpm
    
    log_success "PHP-FPM configurado y reiniciado"
}

# ============================================================
# FASE 7: Configurar Nginx
# ============================================================
configure_nginx() {
    log_info "=== FASE 7: Configurando Nginx ==="
    
    local nginx_conf="/etc/nginx/sites-available/laravel"
    
    cat > "$nginx_conf" << 'EOF'
upstream php_cluster {
    server 192.168.1.11:9000 max_fails=3 fail_timeout=20s;
    server 192.168.1.12:9000 max_fails=3 fail_timeout=20s;
    server 192.168.1.13:9000 max_fails=3 fail_timeout=20s;
    keepalive 16;
}

server {
    listen 80;
    root /var/www/laravel/public;
    index index.php;

    add_header X-Node $hostname always;
    add_header X-Upstream $upstream_addr always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass   php_cluster;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include        fastcgi_params;
        fastcgi_read_timeout 60;
    }

    location /health {
        return 200 "ok\n";
        add_header Content-Type text/plain;
        access_log off;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

    ln -sf /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    nginx -t
    systemctl restart nginx
    systemctl enable nginx
    
    log_success "Nginx configurado"
}

# ============================================================
# FASE 8: Configurar MySQL (si es nodo1)
# ============================================================
configure_mysql() {
    log_info "=== FASE 8: Configurando MySQL ==="
    
    # Instalar MariaDB con soporte Galera
    apt install -y software-properties-common
    
    # Agregar repositorio MariaDB
    curl -sS https://downloads.mariadb.com/MariaDB/mariadb_repo_setup | bash
    
    apt update
    apt install -y mariadb-server galera-4
    
    log_success "MariaDB/Galera instalado"
}

# ============================================================
# FASE 9: Configurar Redis
# ============================================================
configure_redis() {
    log_info "=== FASE 9: Configurando Redis ==="
    
    # Configuración para todos los nodos
    cat >> /etc/redis/redis.conf << EOF

# Configuración para Sentinel
bind 0.0.0.0
port 6379
requirepass $REDIS_PASSWORD
masterauth $REDIS_PASSWORD
EOF

    systemctl restart redis-server
    systemctl enable redis-server
    
    log_success "Redis configurado"
}

# ============================================================
# FASE 10: Configurar Chrony (sincronización)
# ============================================================
configure_chrony() {
    log_info "=== FASE 10: Configurando Chrony (NTP) ==="
    
    if [ "$NODE_ID" = "1" ]; then
        # Nodo 1: Actúa como servidor NTP
        cat > /etc/chrony/chrony.conf << 'EOF'
pool pool.ntp.org iburst
allow 192.168.1.0/24
local stratum 10
driftfile /var/lib/chrony/drift
makestep 1.0 3
rtcsync
logdir /var/log/chrony
EOF
    else
        # Nodos 2 y 3: Sincronizan contra nodo1
        cat > /etc/chrony/chrony.conf << 'EOF'
server 192.168.1.11 iburst prefer
pool pool.ntp.org iburst
driftfile /var/lib/chrony/drift
makestep 1.0 3
rtcsync
logdir /var/log/chrony
EOF
    fi
    
    systemctl restart chrony
    systemctl enable chrony
    
    log_success "Chrony configurado"
}

# ============================================================
# FASE 11: Configurar Keepalived (VIP flotante)
# ============================================================
configure_keepalived() {
    log_info "=== FASE 11: Configurando Keepalived (VIP) ==="
    
    mkdir -p /etc/keepalived
    
    # Script de health check
    cat > /etc/keepalived/check_nginx.sh << 'EOF'
#!/bin/bash
response=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/health)
if [ "$response" = "200" ]; then
    exit 0
else
    exit 1
fi
EOF
    chmod +x /etc/keepalived/check_nginx.sh
    
    # Configuración de Keepalived
    local priority=100
    local state="MASTER"
    
    if [ "$NODE_ID" = "2" ]; then
        priority=90
        state="BACKUP"
    elif [ "$NODE_ID" = "3" ]; then
        priority=80
        state="BACKUP"
    fi
    
    cat > /etc/keepalived/keepalived.conf << EOF
vrrp_script check_nginx {
    script "/etc/keepalived/check_nginx.sh"
    interval 2
    weight -20
    fall 2
    rise 2
}

vrrp_instance VI_1 {
    state $state
    interface $NETWORK_INTERFACE
    virtual_router_id 51
    priority $priority
    advert_int 1
    $([ "$NODE_ID" != "1" ] && echo "nopreempt" || echo "")

    authentication {
        auth_type PASS
        auth_pass sd2024secret
    }

    virtual_ipaddress {
        192.168.1.100/24
    }

    track_script {
        check_nginx
    }
}
EOF

    systemctl restart keepalived
    systemctl enable keepalived
    
    log_success "Keepalived configurado (prioridad: $priority)"
}

# ============================================================
# FASE 12: Configurar Supervisor (Queue workers)
# ============================================================
configure_supervisor() {
    log_info "=== FASE 12: Configurando Supervisor ==="
    
    mkdir -p /etc/supervisor/conf.d
    
    cat > /etc/supervisor/conf.d/laravel-worker.conf << 'EOF'
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/laravel/artisan queue:work redis --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log
stderr_logfile=/var/log/laravel-worker-error.log
EOF

    systemctl restart supervisor
    systemctl enable supervisor
    
    log_success "Supervisor configurado"
}

# ============================================================
# FASE 13: Resumen y verificación
# ============================================================
print_summary() {
    log_info "=== RESUMEN DE CONFIGURACIÓN ==="
    
    echo ""
    echo "Node ID:           $NODE_ID"
    echo "IP Estática:       $NODE_IP"
    echo "Interfaz:          $NETWORK_INTERFACE"
    echo "Gateway:           $GATEWAY_IP"
    echo "PHP Version:       $PHP_VERSION"
    echo "App Path:          $APP_PATH"
    echo ""
    
    # Verificar servicios
    echo "Estado de servicios:"
    echo -n "  PHP-FPM:         "
    systemctl is-active php${PHP_VERSION}-fpm > /dev/null && echo "✓ Activo" || echo "✗ Inactivo"
    
    echo -n "  Nginx:           "
    systemctl is-active nginx > /dev/null && echo "✓ Activo" || echo "✗ Inactivo"
    
    echo -n "  Redis:           "
    systemctl is-active redis-server > /dev/null && echo "✓ Activo" || echo "✗ Inactivo"
    
    echo -n "  Chrony:          "
    systemctl is-active chrony > /dev/null && echo "✓ Activo" || echo "✗ Inactivo"
    
    echo -n "  Keepalived:      "
    systemctl is-active keepalived > /dev/null && echo "✓ Activo" || echo "✗ Inactivo"
    
    echo ""
    echo "Próximos pasos:"
    echo "  1. Copiar el código de la app a: $APP_PATH"
    echo "  2. Ejecutar: cd $APP_PATH && composer install"
    echo "  3. Configurar .env con credenciales de BD"
    echo "  4. Ejecutar migraciones: php artisan migrate"
    echo "  5. Verificar con: curl http://$NODE_IP/health"
    echo ""
    
    log_success "=== INSTALACIÓN COMPLETADA ==="
}

# ============================================================
# Ejecución principal
# ============================================================
main() {
    echo ""
    echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║ Sistema Distribuido - Panadería Wemby                      ║${NC}"
    echo -e "${BLUE}║ Setup Automatizado para Nodo                               ║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    
    check_root
    parse_arguments "$@"
    
    log_info "Iniciando instalación para Nodo $NODE_ID ($NODE_IP)"
    
    update_system
    configure_network
    install_dependencies
    install_composer
    prepare_app_directory
    configure_php_fpm
    configure_nginx
    configure_mysql
    configure_redis
    configure_chrony
    configure_keepalived
    configure_supervisor
    
    print_summary
    
    log_success "Script de instalación finalizado con éxito"
    log_info "Logs guardados en: $LOG_FILE"
}

# Ejecutar
main "$@"
