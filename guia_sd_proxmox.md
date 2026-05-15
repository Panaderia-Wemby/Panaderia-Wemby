# Guía paso a paso — Sistema Distribuido Laravel en Proxmox

**Proyecto:** Réplica de SD tipo Amoeba/Sprite con Laravel  
**Infraestructura:** 3 VMs en Proxmox  
**Tiempo estimado:** 3–4 horas

---

## Mapa de IPs

| Host        | IP            | Rol VRRP  | Prioridad |
|-------------|---------------|-----------|-----------|
| nodo1       | 192.168.1.11  | MASTER    | 100       |
| nodo2       | 192.168.1.12  | BACKUP 1  | 90        |
| nodo3       | 192.168.1.13  | BACKUP 2  | 80        |
| VIP cliente | 192.168.1.100 | Flotante  | —         |

---

## FASE 1 — Crear las VMs en Proxmox

### Paso 1 — Descargar ISO de Ubuntu Server 22.04

En la shell de Proxmox:

```bash
cd /var/lib/vz/template/iso
wget https://releases.ubuntu.com/22.04/ubuntu-22.04.4-live-server-amd64.iso
```

### Paso 2 — Crear VM nodo1 (repetir para nodo2 y nodo3)

En la UI de Proxmox → botón "Create VM":

| Campo           | Valor                        |
|-----------------|------------------------------|
| VM ID           | 101 (102, 103 para los otros) |
| Name            | nodo1 (nodo2, nodo3)         |
| ISO             | ubuntu-22.04.4-live-server   |
| Machine         | q35                          |
| Disk            | 20 GB, virtio, local-lvm     |
| CPUs            | 2 cores                      |
| RAM             | 2048 MB                      |
| Network         | vmbr0, VirtIO                |

> Para heterogeneidad: en nodo3 reducí RAM a 1024 MB y cambiá el tipo de disco a SCSI.

### Paso 3 — Instalar Ubuntu en las 3 VMs

Al arrancar cada VM seguí el instalador:

- Idioma: English
- Network: configura DHCP por ahora, se cambia después
- Storage: Use entire disk
- Usuario: `sduser` / contraseña: `sdpass2024`
- Instalar OpenSSH: **Sí**
- Snaps adicionales: ninguno

Repetí para nodo2 y nodo3.

---

## FASE 2 — Configuración base (en los 3 nodos)

Conectate por SSH a cada nodo. Ejecutá todo esto **en los 3** a menos que se indique lo contrario.

### Paso 4 — Configurar IP estática

Editá el archivo de red de Netplan:

```bash
sudo nano /etc/netplan/00-installer-config.yaml
```

**En nodo1** poné:
```yaml
network:
  version: 2
  ethernets:
    ens18:                       # verificar nombre con: ip link show
      dhcp4: no
      addresses:
        - 192.168.1.11/24
      gateway4: 192.168.1.1
      nameservers:
        addresses: [8.8.8.8, 1.1.1.1]
```

**En nodo2** cambiá la IP a `192.168.1.12`, **en nodo3** a `192.168.1.13`.

```bash
sudo netplan apply
```

Verificá conectividad entre nodos:
```bash
ping -c 3 192.168.1.12    # desde nodo1
ping -c 3 192.168.1.11    # desde nodo2
```

### Paso 5 — Actualizar el sistema e instalar dependencias base

```bash
sudo apt update && sudo apt upgrade -y

sudo apt install -y \
  nginx \
  php8.2-fpm php8.2-mysql php8.2-redis php8.2-xml \
  php8.2-curl php8.2-mbstring php8.2-zip php8.2-gd \
  mysql-client \
  redis-server \
  keepalived \
  chrony \
  supervisor \
  git curl unzip htop
```

> En nodo3 instalá también `php8.3-fpm` en lugar de `php8.2-fpm` para demostrar heterogeneidad.

### Paso 6 — Instalar Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

---

## FASE 3 — Desplegar la aplicación Laravel

### Paso 7 — Clonar o subir el proyecto Laravel

```bash
sudo mkdir -p /var/www/laravel
sudo chown -R sduser:sduser /var/www/laravel

# Opción A: clonar desde tu repositorio
git clone https://github.com/tu-usuario/tu-proyecto.git /var/www/laravel

# Opción B: crear proyecto nuevo
composer create-project laravel/laravel /var/www/laravel
```

### Paso 8 — Configurar permisos y .env

```bash
cd /var/www/laravel
cp .env.example .env

# Generar key solo en nodo1 y luego copiar a los demás
php artisan key:generate
cat .env | grep APP_KEY   # copiar este valor a nodo2 y nodo3
```

Editá `.env` con los mismos valores en los 3 nodos:

```env
APP_NAME="SD-Distribuido"
APP_ENV=production
APP_KEY=base64:TU_KEY_GENERADA_AQUI
APP_DEBUG=false
APP_URL=http://192.168.1.100

DB_CONNECTION=mysql
DB_HOST=192.168.1.11          # Galera: cualquier nodo sirve
DB_PORT=3306
DB_DATABASE=sd_app
DB_USERNAME=sdapp
DB_PASSWORD=sdapppass

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=192.168.1.11       # Se actualizará con Sentinel más adelante
REDIS_PASSWORD=redispass
REDIS_PORT=6379

FILESYSTEM_DISK=local
```

```bash
sudo chown -R www-data:www-data /var/www/laravel/storage
sudo chown -R www-data:www-data /var/www/laravel/bootstrap/cache
sudo chmod -R 775 /var/www/laravel/storage

composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
```

---

## FASE 4 — Configurar PHP-FPM

### Paso 9 — Configurar PHP-FPM para escuchar en red

```bash
# En nodo1 y nodo2 (PHP 8.2)
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

Encontrá la línea `listen` y cambiala:

```ini
; Antes:
listen = /run/php/php8.2-fpm.sock

; Después (escucha en red para que los otros nodos puedan alcanzarla):
listen = 0.0.0.0:9000
```

```bash
# Reiniciar PHP-FPM
sudo systemctl restart php8.2-fpm
sudo systemctl enable php8.2-fpm

# Verificar que está escuchando
ss -tlnp | grep 9000
```

> En nodo3 hacé lo mismo pero con `php8.3-fpm` y el path `/etc/php/8.3/fpm/pool.d/www.conf`.

---

## FASE 5 — Configurar Nginx (cada nodo es LB y app)

### Paso 10 — Crear config de Nginx

```bash
sudo nano /etc/nginx/sites-available/laravel
```

Pegá esto **igual en los 3 nodos** (cada uno balancea hacia los 3 PHP-FPM):

```nginx
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

    # Headers para ver qué nodo físico respondió (útil para demos)
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

    # Endpoint de health check para Keepalived
    location /health {
        return 200 "ok\n";
        add_header Content-Type text/plain;
        access_log off;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t          # verificar configuración
sudo systemctl restart nginx
sudo systemctl enable nginx
```

---

## FASE 6 — MySQL Galera Cluster

### Paso 11 — Instalar Galera en los 3 nodos

```bash
sudo apt install -y software-properties-common
wget https://downloads.mariadb.com/MariaDB/mariadb_repo_setup
chmod +x mariadb_repo_setup
sudo ./mariadb_repo_setup --mariadb-server-version="mariadb-10.11"

sudo apt update
sudo apt install -y mariadb-server galera-4
```

### Paso 12 — Configurar Galera

```bash
sudo nano /etc/mysql/mariadb.conf.d/60-galera.cnf
```

**En nodo1:**
```ini
[mysqld]
binlog_format            = ROW
default-storage-engine   = innodb
innodb_autoinc_lock_mode = 2
bind-address             = 0.0.0.0

# Galera
wsrep_on                 = ON
wsrep_provider           = /usr/lib/galera/libgalera_smm.so
wsrep_cluster_name       = "sd_galera_cluster"
wsrep_cluster_address    = "gcomm://192.168.1.11,192.168.1.12,192.168.1.13"
wsrep_node_address       = "192.168.1.11"
wsrep_node_name          = "nodo1"
wsrep_sst_method         = rsync
```

**En nodo2** cambiá `wsrep_node_address = "192.168.1.12"` y `wsrep_node_name = "nodo2"`.  
**En nodo3** cambiá `wsrep_node_address = "192.168.1.13"` y `wsrep_node_name = "nodo3"`.

### Paso 13 — Arrancar el cluster (orden importa)

```bash
# SOLO EN NODO1 — primera vez
sudo systemctl stop mariadb
sudo galera_new_cluster      # arranca el cluster vacío

# Crear usuario y base de datos (solo en nodo1, se replica automático)
sudo mysql -u root <<EOF
CREATE DATABASE sd_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sdapp'@'%' IDENTIFIED BY 'sdapppass';
GRANT ALL PRIVILEGES ON sd_app.* TO 'sdapp'@'%';
FLUSH PRIVILEGES;
EOF

# Verificar que el cluster tiene 1 miembro
sudo mysql -e "SHOW STATUS LIKE 'wsrep_cluster_size';"
```

```bash
# EN NODO2 — se une al cluster
sudo systemctl start mariadb
sudo mysql -e "SHOW STATUS LIKE 'wsrep_cluster_size';"   # debe decir 2
```

```bash
# EN NODO3 — se une al cluster
sudo systemctl start mariadb
sudo mysql -e "SHOW STATUS LIKE 'wsrep_cluster_size';"   # debe decir 3
```

```bash
# Correr migraciones de Laravel (solo en nodo1)
cd /var/www/laravel
php artisan migrate --force
```

### Paso 14 — Verificar replicación

```bash
# En nodo1 — crear un registro
sudo mysql sd_app -e "CREATE TABLE test (id INT AUTO_INCREMENT PRIMARY KEY, msg VARCHAR(50), nodo VARCHAR(10));"
sudo mysql sd_app -e "INSERT INTO test (msg, nodo) VALUES ('desde nodo1', 'n1');"

# En nodo2 — verificar que replicó
sudo mysql sd_app -e "SELECT * FROM test;"

# En nodo3 — escribir desde otro nodo
sudo mysql sd_app -e "INSERT INTO test (msg, nodo) VALUES ('desde nodo3', 'n3');"

# En nodo1 — verificar la escritura de nodo3
sudo mysql sd_app -e "SELECT * FROM test;"
```

---

## FASE 7 — Redis Sentinel

### Paso 15 — Configurar Redis primario (nodo1)

```bash
sudo nano /etc/redis/redis.conf
```

Cambiá o agregá estas líneas:

```conf
bind 0.0.0.0
port 6379
requirepass redispass
masterauth  redispass
```

```bash
sudo systemctl restart redis-server
```

### Paso 16 — Configurar Redis réplicas (nodo2 y nodo3)

```bash
sudo nano /etc/redis/redis.conf
```

```conf
bind 0.0.0.0
port 6379
requirepass redispass
masterauth  redispass
replicaof 192.168.1.11 6379    # apunta al primario
```

```bash
sudo systemctl restart redis-server

# Verificar replicación desde nodo1
redis-cli -a redispass info replication
# Debe mostrar: connected_slaves:2
```

### Paso 17 — Configurar Redis Sentinel (en los 3 nodos)

```bash
sudo nano /etc/redis/sentinel.conf
```

```conf
port 26379
sentinel monitor mymaster 192.168.1.11 6379 2
sentinel auth-pass mymaster redispass
sentinel down-after-milliseconds mymaster 5000
sentinel failover-timeout mymaster 10000
sentinel parallel-syncs mymaster 1
```

```bash
# Crear servicio systemd para Sentinel
sudo nano /etc/systemd/system/redis-sentinel.service
```

```ini
[Unit]
Description=Redis Sentinel
After=network.target

[Service]
ExecStart=/usr/bin/redis-sentinel /etc/redis/sentinel.conf
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable redis-sentinel
sudo systemctl start redis-sentinel

# Verificar
redis-cli -p 26379 SENTINEL masters
```

---

## FASE 8 — Chrony (sincronización de relojes)

### Paso 18 — Nodo1 actúa como servidor NTP

```bash
sudo nano /etc/chrony/chrony.conf
```

Reemplazá todo el contenido con:

```conf
pool pool.ntp.org iburst
allow 192.168.1.0/24
local stratum 10
driftfile /var/lib/chrony/drift
makestep 1.0 3
rtcsync
logdir /var/log/chrony
```

```bash
sudo systemctl restart chrony
sudo systemctl enable chrony
```

### Paso 19 — Nodo2 y nodo3 sincronizan contra nodo1

```bash
sudo nano /etc/chrony/chrony.conf
```

```conf
server 192.168.1.11 iburst prefer
pool pool.ntp.org iburst
driftfile /var/lib/chrony/drift
makestep 1.0 3
rtcsync
logdir /var/log/chrony
```

```bash
sudo systemctl restart chrony

# Verificar sincronización
chronyc tracking
chronyc sources -v
# El * indica la fuente seleccionada. Offset debe ser < 1ms
```

---

## FASE 9 — Keepalived (VIP flotante)

> Instalá esto **al final**, cuando todo lo demás ya funciona.

### Paso 20 — Script de health check

En los 3 nodos:

```bash
sudo nano /etc/keepalived/check_nginx.sh
```

```bash
#!/bin/bash
# Verifica que Nginx responde al health check local
response=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/health)
if [ "$response" = "200" ]; then
    exit 0
else
    exit 1
fi
```

```bash
sudo chmod +x /etc/keepalived/check_nginx.sh
```

### Paso 21 — Configurar Keepalived nodo1 (MASTER)

```bash
sudo nano /etc/keepalived/keepalived.conf
```

```conf
vrrp_script check_nginx {
    script "/etc/keepalived/check_nginx.sh"
    interval 2
    weight   -20
    fall     2
    rise     2
}

vrrp_instance VI_1 {
    state  MASTER
    interface ens18            # verificar con: ip link show
    virtual_router_id 51
    priority 100
    advert_int 1
    nopreempt

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
```

### Paso 22 — Configurar Keepalived nodo2 (BACKUP 1)

```bash
sudo nano /etc/keepalived/keepalived.conf
```

```conf
vrrp_script check_nginx {
    script "/etc/keepalived/check_nginx.sh"
    interval 2
    weight   -20
    fall     2
    rise     2
}

vrrp_instance VI_1 {
    state  BACKUP
    interface ens18
    virtual_router_id 51
    priority 90
    advert_int 1

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
```

**Nodo3** — igual que nodo2 pero con `priority 80`.

```bash
# Arrancar Keepalived en los 3 nodos
sudo systemctl enable keepalived
sudo systemctl start keepalived

# Verificar que nodo1 tiene la VIP
ip addr show ens18 | grep 192.168.1.100
```

---

## FASE 10 — Queue Workers con Supervisor

### Paso 23 — Configurar Supervisor

```bash
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/laravel/artisan queue:work redis \
    --sleep=3 \
    --tries=3 \
    --timeout=90 \
    --queue=default
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log
stderr_logfile=/var/log/laravel-worker-error.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
sudo supervisorctl status
```

---

## FASE 11 — Verificación completa

### Paso 24 — Probar que el sistema responde

```bash
# Desde cualquier máquina en la red
curl -I http://192.168.1.100
# Revisar cabeceras X-Node y X-Upstream

# Hacer varias peticiones y ver que rota entre nodos
for i in $(seq 1 9); do
    curl -s http://192.168.1.100/health
    curl -sI http://192.168.1.100 | grep X-Node
done
```

### Paso 25 — Probar tolerancia a fallos del LB

```bash
# En nodo1 — detener Nginx
sudo systemctl stop nginx

# Verificar desde otra terminal que la VIP migró a nodo2
ip addr show ens18    # en nodo2 debe aparecer 192.168.1.100

# El sitio sigue respondiendo
curl http://192.168.1.100/health

# Restaurar nodo1
sudo systemctl start nginx
```

### Paso 26 — Probar tolerancia a fallos de base de datos

```bash
# Apagar MySQL en nodo1
sudo systemctl stop mariadb    # en nodo1

# Verificar desde nodo2 que el cluster sigue funcionando con 2 nodos
sudo mysql -e "SHOW STATUS LIKE 'wsrep_cluster_size';"
# debe decir 2

# La app sigue funcionando porque nodo2 y nodo3 tienen la BD
curl http://192.168.1.100

# Restaurar
sudo systemctl start mariadb   # en nodo1
sudo mysql -e "SHOW STATUS LIKE 'wsrep_cluster_size';"   # vuelve a 3
```

### Paso 27 — Probar sincronización de relojes

```bash
# En los 3 nodos simultáneamente
date +"%Y-%m-%d %H:%M:%S.%N"

# Diferencia debe ser < 10ms entre nodos
# Para ver offset exacto:
chronyc tracking | grep "System time"
```

### Paso 28 — Probar redistribución de jobs

```bash
# Despachar un job desde Laravel (crear uno de prueba primero)
php artisan tinker
# >>> dispatch(new App\Jobs\TestJob());
# >>> exit

# Apagar el worker en nodo3
sudo supervisorctl stop laravel-worker:laravel-worker_00

# El job se redistribuye a nodo1 o nodo2 automáticamente
# Verificar en los logs
tail -f /var/log/laravel-worker.log
```

---

## FASE 12 — Comandos útiles para la demo

```bash
# Ver estado de todos los servicios de una vez
sudo systemctl status nginx php8.2-fpm mariadb redis-server keepalived chrony

# Ver qué nodo tiene la VIP actualmente
ip addr show ens18 | grep 192.168.1.100

# Estado del cluster Galera
sudo mysql -e "SHOW STATUS LIKE 'wsrep%';" | grep -E "cluster_size|connected|ready"

# Estado de Redis Sentinel
redis-cli -p 26379 SENTINEL get-master-addr-by-name mymaster

# Sincronización NTP
chronyc sources -v

# Simular carga distribuida y ver rotación
watch -n 0.5 'curl -sI http://192.168.1.100 | grep X-Node'
```

---

## Resumen de mapeo — características del SD

| Característica      | Componente implementado                                    |
|---------------------|------------------------------------------------------------|
| Heterogeneidad      | PHP 8.2 / 8.3, Ubuntu / Alpine, distinto RAM en nodo3     |
| Apertura            | Nginx con API REST estándar HTTP, Galera con protocolo wsrep |
| Seguridad           | Auth Redis/MySQL con contraseñas, Laravel Sanctum          |
| Escalabilidad       | Agregar nodo4 al upstream Nginx + Galera, sin cambiar código |
| Tolerancia a fallos | Keepalived VRRP, Galera multi-master, Redis Sentinel       |
| Concurrencia        | Redis locks, transacciones MySQL, queue workers paralelos  |
| Compartición        | Galera DB, Redis Sentinel, filesystem compartido           |
| Transparencia (8)   | Acceso/Loc/Fallo/Rend: Nginx · Repl: Galera · Migr/Conc: Redis · Escal: upstream dinámico |
