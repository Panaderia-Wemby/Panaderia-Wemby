return [
    /*
    |--------------------------------------------------------------------------
    | Sistema Distribuido Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración específica del Sistema Distribuido con gRPC
    |
    */

    'enabled' => env('DISTRIBUTED_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Identificación del Nodo
    |--------------------------------------------------------------------------
    */
    'node' => [
        'id' => env('NODE_ID', gethostname()),
        'name' => env('NODE_NAME', 'Unknown Node'),
        'ip' => env('NODE_IP', '127.0.0.1'),
        'role' => 'worker',  // 'master', 'worker', 'backup'
    ],

    /*
    |--------------------------------------------------------------------------
    | gRPC Configuration
    |--------------------------------------------------------------------------
    */
    'grpc' => [
        'enabled' => env('GRPC_ENABLED', true),
        'host' => env('GRPC_HOST', '0.0.0.0'),
        'port' => env('GRPC_PORT', 50051),
        'timeout' => env('GRPC_TIMEOUT', 5),
        'ssl' => false,  // Habilitar en producción
    ],

    /*
    |--------------------------------------------------------------------------
    | Nodes Registry
    |--------------------------------------------------------------------------
    | Registro de nodos para sincronización
    */
    'nodes' => [
        'nodo1' => env('GRPC_NODO1', 'localhost:50051'),
        'nodo2' => env('GRPC_NODO2', 'localhost:50052'),
        'nodo3' => env('GRPC_NODO3', 'localhost:50053'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    | Configuración de sincronización entre nodos
    */
    'sync' => [
        'enabled' => true,
        'auto_sync' => true,  // Sincronizar automáticamente en cambios
        'retry_attempts' => 3,
        'retry_delay' => 1000,  // milisegundos

        // Recursos que se sincronizan
        'resources' => [
            'producto' => true,
            'insumo' => true,
            'venta' => true,
            'proveedor' => true,
            'categoria' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    | Verificación de salud entre nodos
    */
    'health_check' => [
        'enabled' => true,
        'interval' => 30,  // segundos
        'timeout' => 5,    // segundos
        'unhealthy_threshold' => 3,  // fallos antes de marcar como unhealthy
    ],

    /*
    |--------------------------------------------------------------------------
    | Clustering
    |--------------------------------------------------------------------------
    | Configuración del cluster Galera MySQL
    */
    'cluster' => [
        'name' => env('GALERA_CLUSTER_NAME', 'sd_galera_cluster'),
        'members' => [
            '192.168.1.11:3306',
            '192.168.1.12:3306',
            '192.168.1.13:3306',
        ],
        'method' => 'galera',  // 'galera', 'replication', 'none'
    ],

    /*
    |--------------------------------------------------------------------------
    | Load Balancer
    |--------------------------------------------------------------------------
    | Configuración del balanceador (Keepalived)
    */
    'load_balancer' => [
        'vip' => env('LB_VIP', '192.168.1.100'),
        'enabled' => true,
        'healthcheck_endpoint' => '/health',
        'healthcheck_interval' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Replication
    |--------------------------------------------------------------------------
    | Configuración de replicación
    */
    'replication' => [
        'method' => 'grpc',  // 'grpc', 'http', 'queue'
        'queue_driver' => 'redis',
        'max_workers' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Failover Configuration
    |--------------------------------------------------------------------------
    */
    'failover' => [
        'enabled' => true,
        'auto_failover' => true,
        'fallback_strategy' => 'local',  // 'local', 'remote', 'hybrid'
        'health_check_retries' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Logging
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => true,
        'log_sync_operations' => true,
        'log_health_checks' => false,  // Verbose, puede llenar logs
        'metrics_enabled' => true,
        'metrics_path' => '/metrics/sd',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'security' => [
        'require_auth' => true,
        'api_key' => env('API_KEY', 'change-me-in-production'),
        'require_ssl' => env('REQUIRE_SSL', false),
        'allowed_ips' => [
            '192.168.1.0/24',  // Tu red interna
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    */
    'debug' => env('SD_DEBUG', false),
];
