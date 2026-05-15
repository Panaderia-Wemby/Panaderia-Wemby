# 🚀 DEPLOYMENT - Sistema Distribuido Panadería Wemby

Guía paso a paso para desplegar el Sistema Distribuido con gRPC en 3 nodos.

## 📋 Pre-requisitos

- ✅ 3 VMs en Proxmox o LXC (Ubuntu 22.04 LTS)
- ✅ Acceso root o sudo en cada VM
- ✅ Red configurada con IPs estáticas
- ✅ 8GB RAM mínimo distribuido (2GB por nodo)
- ✅ Código de la app en Git o similar

---

## 🔧 Fase 0: Preparación

### 0.1 Clonar el repositorio en cada nodo

```bash
# En cada nodo
sudo su -
cd /tmp
git clone https://tu-repo/panaderia-wemby.git /var/www/laravel
cd /var/www/laravel
```

### 0.2 Verificar conectividad entre nodos

```bash
# Desde nodo1:
ping 192.168.1.12  # Nodo 2
ping 192.168.1.13  # Nodo 3

# Debe haber respuesta en ambos
```

---

## 🔨 Fase 1: Instalación automática (5-10 min por nodo)

### 1.1 Ejecutar setup en cada nodo

**Nodo 1:**
```bash
cd /var/www/laravel
sudo bash scripts/setup-sd.sh --node 1 --ip 192.168.1.11 --gateway 192.168.1.1
```

**Nodo 2:**
```bash
sudo bash scripts/setup-sd.sh --node 2 --ip 192.168.1.12 --gateway 192.168.1.1
```

**Nodo 3:**
```bash
sudo bash scripts/setup-sd.sh --node 3 --ip 192.168.1.13 --gateway 192.168.1.1
```

⏱️ **Esto toma ~5-10 min por nodo. Esperar a que complete.**

### 1.2 Verificar que servicios están activos

```bash
sudo systemctl status php8.2-fpm nginx mariadb redis-server chrony keepalived
```

Debería ver todos en verde ✓

---

## 🗄️ Fase 2: Configurar Base de Datos Galera (10 min)

### 2.1 Nodo 1: Inicializar cluster

```bash
# En Nodo 1
sudo systemctl stop mariadb

# Inicializar como primer nodo
sudo galera_new_cluster

# Esperar 3-5 segundos
sleep 5

# Crear usuario y BD
sudo mysql -u root <<EOF
CREATE DATABASE sd_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sdapp'@'%' IDENTIFIED BY 'sdapppass';
GRANT ALL PRIVILEGES ON sd_app.* TO 'sdapp'@'%';
FLUSH PRIVILEGES;
EOF

# Verificar cluster size
sudo mysql -e "SHOW STATUS LIKE 'wsrep_cluster_size';"
# Debe decir: wsrep_cluster_size | 1
```

### 2.2 Nodo 2: Unirse al cluster

```bash
# En Nodo 2
sudo systemctl start mariadb

# Esperar 3-5 segundos
sleep 5

# Verificar que se unió
sudo mysql -e "SHOW STATUS LIKE 'wsrep_cluster_size';"
# Debe decir: wsrep_cluster_size | 2
```

### 2.3 Nodo 3: Unirse al cluster

```bash
# En Nodo 3
sudo systemctl start mariadb

# Esperar 3-5 segundos
sleep 5

# Verificar cluster completo
sudo mysql -e "SHOW STATUS LIKE 'wsrep_cluster_size';"
# Debe decir: wsrep_cluster_size | 3
```

✅ **Cluster Galera está listo con 3 nodos**

---

## 📦 Fase 3: Instalar aplicación Laravel

Ejecutar en **TODOS** los nodos (paralelo es ok):

```bash
# En cada nodo
cd /var/www/laravel

# 1. Copiar .env
cp .env.example.sd .env

# 2. Instalar dependencias (incluir gRPC)
composer install --no-dev --optimize-autoloader

# 3. Solo en Nodo1: Generar APP_KEY
php artisan key:generate

# 4. Copiar APP_KEY a otros nodos
# Desde Nodo1, obtener el key:
grep APP_KEY .env | cut -d'=' -f2
# Luego pegarlo en .env de Nodo2 y Nodo3

# 5. Preparar directorios
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 6. Cache
php artisan config:cache
php artisan route:cache
```

---

## 🗃️ Fase 4: Correr migraciones (5 min)

Solo en **Nodo 1** (se replica automáticamente a los demás):

```bash
# En Nodo 1
cd /var/www/laravel
php artisan migrate --force

# Verificar
php artisan migrate:status
```

Verificar en **Nodo 2 y 3** que las tablas se replicaron:

```bash
# En Nodo 2/3
mysql sd_app -e "SHOW TABLES;"
# Debe mostrar: productos, insumos, ventas, etc.
```

✅ **BD completamente sincronizada entre los 3 nodos**

---

## 🔗 Fase 5: Configurar Redis Sentinel (5 min)

### 5.1 Crear configuración Sentinel en cada nodo

```bash
# En los 3 nodos
sudo tee /etc/redis/sentinel.conf > /dev/null << 'EOF'
port 26379
sentinel monitor mymaster 192.168.1.11 6379 2
sentinel auth-pass mymaster redispass
sentinel down-after-milliseconds mymaster 5000
sentinel failover-timeout mymaster 10000
sentinel parallel-syncs mymaster 1
EOF

# Crear servicio systemd
sudo tee /etc/systemd/system/redis-sentinel.service > /dev/null << 'EOF'
[Unit]
Description=Redis Sentinel
After=network.target

[Service]
ExecStart=/usr/bin/redis-sentinel /etc/redis/sentinel.conf
Restart=always

[Install]
WantedBy=multi-user.target
EOF

# Iniciar Sentinel
sudo systemctl daemon-reload
sudo systemctl enable redis-sentinel
sudo systemctl start redis-sentinel
```

### 5.2 Verificar Sentinel

```bash
# En cualquier nodo
redis-cli -p 26379 SENTINEL masters
# Debe mostrar: mymaster
```

✅ **Redis Sentinel configurado**

---

## 🎯 Fase 6: Verificar Load Balancer (Keepalived)

### 6.1 Verificar VIP flotante

```bash
# En Nodo 1 (debería tener la VIP)
ip addr show | grep 192.168.1.100
# Debe mostrar: inet 192.168.1.100/24

# En Nodo 2 y 3 (no debe aparecer)
ip addr show | grep 192.168.1.100
# No debe mostrar nada
```

### 6.2 Probar failover manual

```bash
# En Nodo 1: Detener Nginx
sudo systemctl stop nginx

# Esperar 3-5 segundos

# Verificar que VIP migró a Nodo 2
# En Nodo 2:
ip addr show | grep 192.168.1.100
# Debería aparecer ahora

# Restaurar Nodo 1
sudo systemctl start nginx
ip addr show | grep 192.168.1.100
# Debería reclamar la VIP después de 10 segundos
```

✅ **Load Balancer funcionando**

---

## 🌐 Fase 7: Pruebas de conectividad

### 7.1 Verificar que la app responde

```bash
# Desde cualquier máquina de la red
curl -v http://192.168.1.100/health
# Debería retornar 200 OK con "ok"

# Ver cabeceras de debug
curl -I http://192.168.1.100
# Verá: X-Node, X-Upstream, X-Response-Time
```

### 7.2 Probar rotación de nodos

```bash
# Ejecutar varias peticiones
for i in {1..9}; do
    curl -sI http://192.168.1.100 | grep X-Node
    echo "---"
done

# Debería ver rotación entre: nodo1, nodo2, nodo3
```

### 7.3 Probar sincronización de BD

```bash
# En Nodo 1: Insertar datos
mysql sd_app -e "INSERT INTO categorias (nombre, descripcion) VALUES ('Pan', 'Categoría de pan');"

# En Nodo 2: Verificar que replicó
mysql sd_app -e "SELECT * FROM categorias WHERE nombre='Pan';"

# En Nodo 3: Insertar desde otro nodo
mysql sd_app -e "INSERT INTO categorias (nombre, descripcion) VALUES ('Pastas', 'Categoría de pastas');"

# En Nodo 1: Verificar
mysql sd_app -e "SELECT COUNT(*) FROM categorias;"
# Debe mostrar 2
```

✅ **Replicación de BD funcionando**

---

## 🔐 Fase 8: Configuración de seguridad

### 8.1 Cambiar contraseñas en producción

```bash
# MySQL
sudo mysql -u root <<EOF
ALTER USER 'sdapp'@'%' IDENTIFIED BY 'nueva-contraseña-fuerte';
FLUSH PRIVILEGES;
EOF

# Redis
sudo redis-cli -a redispass CONFIG SET requirepass nueva-contraseña-fuerte

# Actualizar .env en todos los nodos
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=nueva-contraseña-fuerte/" /var/www/laravel/.env
sed -i "s/REDIS_PASSWORD=.*/REDIS_PASSWORD=nueva-contraseña-fuerte/" /var/www/laravel/.env
```

### 8.2 Configurar firewall

```bash
# UFW (en cada nodo)
sudo ufw enable
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw allow from 192.168.1.0/24 to any port 3306   # MySQL
sudo ufw allow from 192.168.1.0/24 to any port 6379   # Redis
sudo ufw allow from 192.168.1.0/24 to any port 50051  # gRPC
```

---

## 📊 Fase 9: Monitoring y Logs

### 9.1 Ver logs en tiempo real

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

### 9.2 Verificar estados

```bash
# Estado general del sistema
systemctl status nginx php8.2-fpm mariadb redis-server keepalived chrony

# Ver worker queue
supervisor

# Ver estadísticas de cluster
mysql -e "SHOW STATUS LIKE 'wsrep%';" | grep -E "cluster_size|connected|ready"

# Ver estado NTP
chronyc tracking
chronyc sources -v
```

---

## 📈 Fase 10: Despliegue de código

### 10.1 Actualizar código en todos los nodos

```bash
# Opción 1: Git pull
cd /var/www/laravel
git pull origin main
composer install --no-dev --optimize-autoloader

# Opción 2: rsync desde Nodo 1
rsync -avz --delete /var/www/laravel/ sduser@192.168.1.12:/var/www/laravel/
rsync -avz --delete /var/www/laravel/ sduser@192.168.1.13:/var/www/laravel/
```

### 10.2 Ejecutar comandos artisan distribuidos

```bash
# En Nodo 1: Migraciones (automáticamente se replican a todos)
php artisan migrate

# Cache en todos los nodos
for i in 1 2 3; do
    ssh sduser@192.168.1.1$i "cd /var/www/laravel && php artisan cache:clear"
done
```

---

## ✅ Checklist de Verificación Final

```
[ ] 3 nodos con IPs estáticas configuradas
[ ] Ping exitoso entre nodos
[ ] MySQL Galera con 3 miembros en cluster
[ ] Redis Sentinel funcionando en los 3 nodos
[ ] Keepalived con VIP flotante en 192.168.1.100
[ ] Nginx respondiendo en puertos 80/443
[ ] PHP-FPM escuchando en puerto 9000
[ ] gRPC server escuchando en puerto 50051
[ ] Laravel migraciones completadas
[ ] Chrony sincronizando relojes (offset < 10ms)
[ ] Health check en /health respondiendo 200 OK
[ ] Datos replicándose entre nodos
[ ] Load balancer rotando requests
[ ] Failover manual funcionando
[ ] Logs centralizados y monitoreados
[ ] Firewall configurado
[ ] Backups automáticos configurados (recomendado)
```

---

## 🆘 Troubleshooting

### Galera no sincroniza
```bash
# Verificar estado del cluster
mysql -e "SHOW STATUS LIKE 'wsrep%';" | head -20

# Reiniciar nodo problemático
sudo systemctl restart mariadb

# Si aún no se une, rebootear el nodo
sudo reboot
```

### Keepalived no mueve VIP
```bash
# Verificar script de health check
sudo /etc/keepalived/check_nginx.sh
echo $?  # debe ser 0

# Ver logs
sudo journalctl -u keepalived -n 50
```

### Redis no reparte
```bash
# Verificar conexión
redis-cli -h 192.168.1.11 -a redispass ping
redis-cli -h 192.168.1.12 -a redispass ping
redis-cli -h 192.168.1.13 -a redispass ping

# Todos deben responder: PONG
```

### Sincronización DB lenta
```bash
# Verificar network entre nodos
ping -c 100 192.168.1.12 | tail -1  # chequear min/avg/max

# Aumentar buffer Galera si es necesario
# En /etc/mysql/mariadb.conf.d/60-galera.cnf
# wsrep_max_ws_size = 2G
```

---

## 📞 Soporte

Cualquier problema, revisar:
1. Logs del servicio: `journalctl -u servicio -n 100`
2. Conectividad de red: `tcpdump -i any -n | grep 192.168.1`
3. Estado de cluster: `mysql -e "SHOW STATUS LIKE 'wsrep%';"`
4. Documentación: `guia_sd_proxmox.md`

---

**Última actualización:** Mayo 2026
