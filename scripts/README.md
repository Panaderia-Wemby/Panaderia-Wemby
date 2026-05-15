# 🐳 Docker + gRPC - Sistema Distribuido Panadería Wemby

Guía completa para dockerizar la aplicación Laravel y desplegarla como un Sistema Distribuido (SD) con 3 nodos usando gRPC para sincronización.

## 📋 Tabla de Contenidos

- [Estructura](#estructura)
- [Desarrollo Local con Docker](#desarrollo-local)
- [Despliegue en 3 Nodos (Producción)](#despliegue-3-nodos)
- [Configuración gRPC](#grpc)
- [Troubleshooting](#troubleshooting)

---

## 📁 Estructura

```
project/
├── Dockerfile                      # Multi-stage, instala Laravel desde 0
├── docker-compose.yml              # Servicios para desarrollo local
├── docker/
│   ├── php-fpm.conf               # Configuración PHP-FPM
│   ├── nginx.conf                 # Configuración Nginx
│   ├── laravel.conf               # Virtual host Laravel
│   └── mysql.cnf                  # Configuración MariaDB Galera
├── proto/
│   └── data_sync.proto            # Definición servicios gRPC
├── app/Grpc/
│   ├── DataSyncService.php        # Implementación del servicio
│   ├── Client.php                 # Cliente gRPC
│   └── Server.php                 # Servidor gRPC
└── scripts/
    ├── setup-sd.sh                # Instalación automatizada para LXC/VM
    └── deploy-docker.sh           # Helper para docker-compose
```

---

## 🐳 Desarrollo Local con Docker

### Requisitos

- Docker 20+
- Docker Compose 1.29+
- 4GB RAM disponible

### Quick Start

```bash
# 1. Clonar y posicionarse
cd ~/Documentos/Panaderia-Wemby

# 2. Construir imagen
./scripts/deploy-docker.sh build

# 3. Levantar servicios
./scripts/deploy-docker.sh up

# 4. Correr migraciones
./scripts/deploy-docker.sh migrate

# 5. Acceder a la app
http://localhost
```

### Servicios Disponibles

| Servicio      | URL/Puerto     | Credenciales            |
|---------------|----------------|-------------------------|
| App (Nginx)   | `localhost:80` | -                       |
| PHP-FPM       | `localhost:9000` | -                      |
| gRPC          | `localhost:50051` | -                      |
| MySQL         | `localhost:3306` | `sdapp / sdapppass`    |
| Redis         | `localhost:6379` | password: `redispass` |
| PhpMyAdmin    | `localhost:8081` | `sdapp / sdapppass`    |

### Comandos Útiles

```bash
# Ver logs
./scripts/deploy-docker.sh logs app1
./scripts/deploy-docker.sh logs mysql
./scripts/deploy-docker.sh logs nginx

# Abrir shell
./scripts/deploy-docker.sh shell-app
./scripts/deploy-docker.sh shell-db

# Tinker REPL
./scripts/deploy-docker.sh tinker

# Health check
./scripts/deploy-docker.sh health

# Reset completo
./scripts/deploy-docker.sh fresh
```

---

## 🚀 Despliegue en 3 Nodos (Producción)

Sigue la estructura de la **guía_sd_proxmox.md** para configurar los 3 nodos en Proxmox, luego usa el script de instalación automática.

### Paso 1: Preparar VMs/LXCs en Proxmox

```bash
# En cada VM/LXC: Ubuntu Server 22.04 LTS
# Recursos recomendados:
# - CPU: 2 cores
# - RAM: 2GB
# - Disk: 20GB
# - Network: Bridge a LAN

# IPs sugeridas (reemplazar según tu red):
# Nodo 1: 192.168.1.11
# Nodo 2: 192.168.1.12
# Nodo 3: 192.168.1.13
# VIP flotante: 192.168.1.100
```

### Paso 2: Ejecutar instalación automatizada

**En Nodo 1:**
```bash
sudo ./scripts/setup-sd.sh --node 1 --ip 192.168.1.11 --gateway 192.168.1.1
```

**En Nodo 2:**
```bash
sudo ./scripts/setup-sd.sh --node 2 --ip 192.168.1.12 --gateway 192.168.1.1
```

**En Nodo 3:**
```bash
sudo ./scripts/setup-sd.sh --node 3 --ip 192.168.1.13 --gateway 192.168.1.1
```

### Paso 3: Copiar y configurar la app

En cada nodo:

```bash
# Copiar código (opción: git clone o rsync)
git clone https://repo/panaderia-wemby.git /var/www/laravel
cd /var/www/laravel

# Instalar dependencias
composer install --no-dev --optimize-autoloader

# Crear .env desde .env.example
cp .env.example .env

# Generar APP_KEY (solo en nodo1, luego copiar a los demás)
php artisan key:generate

# Ajustar permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage

# Cache
php artisan config:cache
php artisan route:cache
```

### Paso 4: Configurar Galera (MySQL Cluster)

**Nodo 1 (initializer):**
```bash
sudo systemctl stop mariadb
sudo galera_new_cluster
sudo mysql -u root <<EOF
CREATE DATABASE sd_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sdapp'@'%' IDENTIFIED BY 'sdapppass';
GRANT ALL PRIVILEGES ON sd_app.* TO 'sdapp'@'%';
FLUSH PRIVILEGES;
EOF

# Verificar cluster
mysql -e "SHOW STATUS LIKE 'wsrep_cluster_size';"  # debe decir 1
```

**Nodo 2:**
```bash
sudo systemctl start mariadb
mysql -e "SHOW STATUS LIKE 'wsrep_cluster_size';"  # debe decir 2
```

**Nodo 3:**
```bash
sudo systemctl start mariadb
mysql -e "SHOW STATUS LIKE 'wsrep_cluster_size';"  # debe decir 3
```

**Nodo 1: Correr migraciones**
```bash
cd /var/www/laravel
php artisan migrate --force
```

### Paso 5: Verificación

```bash
# Verificar VIP en Nodo 1
ip addr show | grep 192.168.1.100

# Acceder a la app
curl http://192.168.1.100/health

# Probar load balancing (múltiples peticiones)
for i in {1..9}; do
    curl -sI http://192.168.1.100 | grep X-Node
done

# Verificar sincronización BD
# En Nodo 1
mysql sd_app -e "INSERT INTO test VALUES (NULL, 'desde nodo1', NOW());"

# En Nodo 2
mysql sd_app -e "SELECT * FROM test;"  # debe mostrar el insert de nodo1
```

---

## 🔗 gRPC - Sincronización entre Nodos

### Arquitectura

```
┌────────────────────────────────────────────────┐
│         Nodo 1 (MASTER)                        │
│  ┌──────────────────────────────────────────┐  │
│  │  Laravel App + PHP-FPM (9000)            │  │
│  │  gRPC Server (50051)                     │  │
│  │  DataSyncService                         │  │
│  └──────────────────────────────────────────┘  │
└────────────────────────────────────────────────┘
         │ gRPC Sync │  HTTP Health Check
         ▼           ▼
    ┌─────────────────────────┐  ┌─────────────────────────┐
    │ Nodo 2 (BACKUP 1)       │  │ Nodo 3 (BACKUP 2)       │
    │ gRPC Server (50051)     │  │ gRPC Server (50051)     │
    └─────────────────────────┘  └─────────────────────────┘
```

### Servicios gRPC Disponibles

Definido en `proto/data_sync.proto`:

#### 1. **SyncData** - Sincroniza cambios entre nodos

```protobuf
rpc SyncData (SyncRequest) returns (SyncResponse);
```

Parámetros:
- `resource_type`: "producto", "insumo", "venta", etc.
- `action`: "create", "update", "delete"
- `data`: JSON del recurso

**Uso en código:**

```php
use App\Grpc\DataSyncClient;

// Crear cliente
$client = new DataSyncClient();

// Sincronizar un nuevo producto
$client->sync('producto', 'create', [
    'nombre' => 'Pan Integral',
    'precio' => 5.50,
    'stock' => 100,
]);
```

#### 2. **HealthCheck** - Verifica salud de nodos

```protobuf
rpc HealthCheck (HealthCheckRequest) returns (HealthCheckResponse);
```

Devuelve:
- `status`: "healthy", "degraded", "unhealthy"
- `load`: % de carga (0-100)
- `active_connections`: conexiones activas

#### 3. **QuerySync** - Ejecuta queries sincronizadas

```protobuf
rpc QuerySync (DatabaseSyncRequest) returns (DatabaseSyncResponse);
```

---

## ⚙️ Middleware de Sincronización

Crear un middleware que sincronice cambios automáticamente:

```php
// app/Http/Middleware/SyncDistributed.php

namespace App\Http\Middleware;

use App\Grpc\DataSyncClient;
use Closure;

class SyncDistributed
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Si es POST/PUT/DELETE, sincronizar
        if (in_array($request->method(), ['POST', 'PUT', 'DELETE'])) {
            $client = new DataSyncClient();
            
            // Ejemplo: si crea un producto
            if (str_contains($request->path(), 'productos')) {
                $client->sync('producto', strtolower($request->method()), $request->all());
            }
        }

        return $response;
    }
}
```

Registrar en `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ...
    \App\Http\Middleware\SyncDistributed::class,
];
```

---

## 🔧 Troubleshooting

### Docker

**Error: "Port 80 is already in use"**
```bash
# Cambiar puerto en docker-compose.yml
docker-compose down
# Editar: ports: - "8080:80"
docker-compose up -d
```

**PHP-FPM no responde**
```bash
./scripts/deploy-docker.sh logs app1
# Verificar: fastcgi_pass debe ser app1:9000
```

**Redis no conecta**
```bash
./scripts/deploy-docker.sh shell-app
php artisan tinker
>>> Cache::get('test')  # verificar conexión
```

### Producción (SD)

**Keepalived no balancea**
```bash
# Verificar salud de script
sudo /etc/keepalived/check_nginx.sh

# Ver status de VRRP
sudo systemctl status keepalived
sudo journalctl -u keepalived -n 50
```

**Galera no replica**
```bash
# Ver estado del cluster
mysql -e "SHOW STATUS LIKE 'wsrep%';"

# Verificar conectividad entre nodos
sudo tcpdump -i any -n dst 192.168.1.12 or dst 192.168.1.13
```

**gRPC no sincroniza**
```bash
# Verificar logs
tail -f /var/log/laravel-worker.log

# Testear conectividad gRPC
php -r "echo shell_exec('nc -zv 192.168.1.12 50051');"
```

---

## 📖 Referencias

- **Guía SD Proxmox**: `guia_sd_proxmox.md`
- **Dockerfile Multi-stage**: Instala Laravel desde `composer.json`
- **gRPC PHP**: https://github.com/grpc/grpc
- **Laravel gRPC**: Middleware + Client custom

---

## 🤝 Soporte

Para errores o dudas:
1. Revisar logs: `./scripts/deploy-docker.sh logs`
2. Ejecutar health check: `./scripts/deploy-docker.sh health`
3. Consultar guía_sd_proxmox.md

---

**Última actualización:** Mayo 2026
