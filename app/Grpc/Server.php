<?php

namespace App\Grpc;

use Grpc\RpcServer;
use Illuminate\Support\Facades\Log;

/**
 * Servidor gRPC para DataSync
 * 
 * Ejecutar con: php app/Grpc/Server.php
 */
class Server
{
    private $server;
    private $port;

    public function __construct($port = 50051)
    {
        $this->port = $port;
    }

    public function start()
    {
        Log::info("Iniciando servidor gRPC en puerto {$this->port}");

        $this->server = new RpcServer();

        // Registrar servicio
        $this->server->addService(
            \Panaderia\Grpc\DataSyncClient::class,
            new DataSyncService()
        );

        $this->server->addListeningPort("0.0.0.0:{$this->port}", []);

        Log::info("Servidor gRPC iniciado correctamente");

        // Mantener el servidor ejecutándose
        $this->server->run();
    }
}

// Script de inicio si se ejecuta directamente
if (php_sapi_name() == 'cli' && count($_SERVER['argv']) > 0) {
    require __DIR__ . '/../../bootstrap/app.php';

    $app = app();
    $port = env('GRPC_PORT', 50051);

    $server = new Server($port);
    $server->start();
}
