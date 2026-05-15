# 🎯 Quick Reference - Comandos útiles para SD

## 🐳 Docker (Desarrollo)

```bash
# Build
./scripts/deploy-docker.sh build

# Up
./scripts/deploy-docker.sh up

# Logs
./scripts/deploy-docker.sh logs app1
./scripts/deploy-docker.sh logs nginx
./scripts/deploy-docker.sh logs mysql

# Shell
./scripts/deploy-docker.sh shell-app
./scripts/deploy-docker.sh shell-db

# Tinker
./scripts/deploy-docker.sh tinker

# Reset
./scripts/deploy-docker.sh fresh

# Health
./scripts/deploy-docker.sh health

# Down
./scripts/deploy-docker.sh down
```

## 🚀 Producción (LXC/VM 3 nodos)

### Instalación inicial

```bash
# Nodo 1
sudo bash scripts/setup-sd.sh --node 1 --ip 192.168.1.11

# Nodo 2
sudo bash scripts/setup-sd.sh --node 2 --ip 192.168.1.12

# Nodo 3
sudo bash scripts/setup-sd.sh --node 3 --ip 192.168.1.13
```

### Galera MySQL

```bash
# Nodo 1: Inicializar
sudo systemctl stop mariadb
sudo galera_new_cluster
sudo mysql -u root <<EOF
CREATE DATABASE sd_app CHARACTER SET utf8mb4;
CREATE USER 'sdapp'@'%' IDENTIFIED BY 'sdapppass';
GRANT ALL PRIVILEGES ON sd_app.* TO 'sdapp'@'%';
FLUSH PRIVILEGES;
EOF

# Nodo 2, 3: Unirse
sudo systemctl start mariadb

# Verificar (todos)
sudo mysql -e "SHOW STATUS LIKE 'wsrep_cluster_size';"
```

### Verificaciones rápidas

```bash
# Cluster status
mysql -e "SHOW STATUS LIKE 'wsrep%';" | grep cluster_size

# Redis Sentinel
redis-cli -p 26379 SENTINEL masters

# VIP Keepalived
ip addr show | grep 192.168.1.100

# Health check app
curl http://192.168.1.100/health

# Test load balancing
for i in {1..5}; do curl -sI http://192.168.1.100 | grep X-Node; done
```

### Logs

```bash
# PHP-FPM
sudo tail -f /var/log/php8.2-fpm.log

# Nginx
sudo tail -f /var/log/nginx/error.log

# MySQL
sudo tail -f /var/log/mysql/error.log

# Redis
sudo tail -f /var/log/redis/redis-server.log

# Keepalived
sudo journalctl -u keepalived -f

# Laravel
tail -f /var/www/laravel/storage/logs/laravel.log
```

### Servicios

```bash
# Estado todos
systemctl status nginx php8.2-fpm mariadb redis-server keepalived chrony

# Reiniciar individual
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
sudo systemctl restart mariadb
sudo systemctl restart redis-server
sudo systemctl restart keepalived
sudo systemctl restart chrony
```

## 🔗 gRPC

### Verificar gRPC escuchando

```bash
# Debe escuchar en puerto 50051
ss -tlnp | grep 50051

# Desde otro nodo
nc -zv 192.168.1.11 50051  # conectado: OK
```

### Test gRPC manual

```bash
# Desde app
cd /var/www/laravel
php artisan tinker

>>> $client = new \App\Grpc\DataSyncClient();
>>> $client->sync('producto', 'create', ['nombre' => 'Test', 'precio' => 10]);
>>> $client->checkNodeHealth('192.168.1.12:50051');
```

## 🔄 Replicación & Sincronización

### Forzar sincronización

```bash
# Desde app
php artisan tinker
>>> DB::table('productos')->insert(['nombre' => 'Test', 'precio' => 9.99]);
>>> Cache::put('test', 'value');
>>> dispatch(new \App\Jobs\SyncJob('producto', 'create', [...] ));
```

### Ver qué se está replicando

```bash
# En Nodo 1: Insert
mysql sd_app -e "INSERT INTO productos (nombre, precio) VALUES ('Pan', 5.50);"

# En Nodo 2: Verificar
mysql sd_app -e "SELECT * FROM productos WHERE nombre='Pan';"

# Mismo en Nodo 3
mysql sd_app -e "SELECT * FROM productos WHERE nombre='Pan';"
```

## 📊 Monitoreo

### System Load

```bash
# Carga del sistema
top -b -n 1 | head -20

# CPU/Memory por proceso
ps aux --sort=-%cpu | head -10
ps aux --sort=-%mem | head -10
```

### Conexiones

```bash
# Conexiones MySQL
mysql -e "SHOW PROCESSLIST;"
mysql -e "SHOW STATUS LIKE 'Threads_connected';"

# Conexiones Redis
redis-cli INFO clients

# Conexiones Nginx
sudo netstat -an | grep :80 | wc -l
```

### Sincronización de relojes

```bash
# Chrony
chronyc tracking
chronyc sources -v

# Ver offset (debe ser < 10ms)
chronyc tracking | grep System
```

## 🔧 Tareas comunes

### Copiar .env a todos los nodos

```bash
scp /var/www/laravel/.env sduser@192.168.1.12:/var/www/laravel/
scp /var/www/laravel/.env sduser@192.168.1.13:/var/www/laravel/
```

### Recolectar logs de todos los nodos

```bash
# Crear directorio
mkdir -p ./logs/{nodo1,nodo2,nodo3}

# Copiar logs
for i in 1 2 3; do
    scp sduser@192.168.1.1$i:/var/www/laravel/storage/logs/laravel.log ./logs/nodo$i/
done

# Ver todos
tail -f ./logs/*/laravel.log
```

### Backup de BD

```bash
# Desde cualquier nodo (Galera replica)
mysqldump -u sdapp -psdapppass sd_app > sd_app_backup_$(date +%Y%m%d_%H%M%S).sql

# Restaurar
mysql -u sdapp -psdapppass sd_app < sd_app_backup_20260515_143022.sql
```

### Resetear worker queue

```bash
# Purgar todas las jobs
redis-cli -a redispass FLUSHDB

# Verificar
redis-cli -a redispass DBSIZE  # debe decir 0
```

## 🚨 Troubleshooting rápido

### App no responde en 192.168.1.100

```bash
# 1. Verificar VIP está en algún nodo
for i in 1 2 3; do
    echo "Nodo $i:"
    ssh sduser@192.168.1.1$i "ip addr show | grep 100"
done

# 2. Verificar Nginx está activo
sudo systemctl status nginx

# 3. Reiniciar Keepalived en el nodo con VIP
sudo systemctl restart keepalived
```

### Galera no sincroniza

```bash
# Verificar cluster
mysql -e "SHOW STATUS LIKE 'wsrep_cluster%';"

# Si alguien está desconectado
# En ese nodo:
sudo systemctl restart mariadb

# Esperar 10 segundos y verificar nuevamente
sleep 10
mysql -e "SHOW STATUS LIKE 'wsrep_cluster_size';"
```

### Redis no conecta

```bash
# Verificar servicio activo
sudo systemctl status redis-server

# Verificar puerto 6379 escuchando
sudo netstat -tlnp | grep 6379

# Test conexión
redis-cli -h 127.0.0.1 -a redispass ping  # PONG
```

### PHP-FPM error

```bash
# Logs
sudo tail -f /var/log/php8.2-fpm.log

# Verificar escuchando
sudo netstat -tlnp | grep 9000

# Reiniciar
sudo systemctl restart php8.2-fpm
```

---

**Tip:** Bookmark esta página para referencia rápida durante operaciones.
