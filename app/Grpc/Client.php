<?php

namespace App\Grpc;

use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Cliente gRPC para comunicación con otros nodos del SD
 */
class DataSyncClient
{
    protected $nodes = [];
    protected $timeout = 5;

    public function __construct()
    {
        // Leer configuración de nodos desde env o config
        $this->nodes = [
            'nodo1' => env('GRPC_NODO1', 'app1:50051'),
            'nodo2' => env('GRPC_NODO2', 'app2:50051'),
            'nodo3' => env('GRPC_NODO3', 'app3:50051'),
        ];
    }

    /**
     * Sincroniza un cambio a los demás nodos
     * 
     * @param string $resourceType
     * @param string $action (create|update|delete)
     * @param array $data
     * @return bool
     */
    public function sync($resourceType, $action, $data)
    {
        $currentNode = env('NODE_ID', gethostname());
        
        Log::info("gRPC Sync initiated", [
            'resource_type' => $resourceType,
            'action' => $action,
            'node' => $currentNode,
        ]);

        $results = [];

        foreach ($this->nodes as $nodeName => $nodeAddress) {
            // No sincronizar con el mismo nodo
            if ($nodeName === $currentNode) {
                continue;
            }

            try {
                $result = $this->sendSync(
                    $nodeAddress,
                    $resourceType,
                    $action,
                    $data,
                    $currentNode
                );
                $results[$nodeName] = $result;
            } catch (Exception $e) {
                Log::warning("gRPC Sync failed for $nodeName: " . $e->getMessage());
                $results[$nodeName] = false;
            }
        }

        // Retornar true si al menos un nodo se sincronizó exitosamente
        return count(array_filter($results)) > 0 || count($results) === 0;
    }

    /**
     * Envía sincronización a un nodo específico
     */
    protected function sendSync($nodeAddress, $resourceType, $action, $data, $currentNode)
    {
        try {
            list($host, $port) = explode(':', $nodeAddress);

            $context = stream_context_create([
                'grpc' => [
                    'timeout' => $this->timeout,
                ]
            ]);

            // Aquí irá la implementación real con gRPC client
            // Por ahora, hacer fallback a HTTP
            return $this->sendSyncViaHttp($nodeAddress, $resourceType, $action, $data);

        } catch (Exception $e) {
            Log::error("Failed to sync to $nodeAddress: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fallback: sincronización via HTTP (para desarrollo)
     */
    protected function sendSyncViaHttp($nodeAddress, $resourceType, $action, $data)
    {
        try {
            $url = "http://{$nodeAddress}/api/sync";

            $payload = [
                'resource_type' => $resourceType,
                'action' => $action,
                'data' => $data,
                'node_id' => env('NODE_ID', gethostname()),
                'timestamp' => time(),
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-API-Key: ' . env('API_KEY', 'secret'),
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode >= 200 && $httpCode < 300;

        } catch (Exception $e) {
            Log::error("HTTP Sync failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica la salud de otros nodos
     */
    public function checkNodeHealth($nodeAddress)
    {
        try {
            $url = "http://{$nodeAddress}/health";
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtiene la configuración de nodos
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * Agrega un nodo a la lista
     */
    public function addNode($nodeName, $nodeAddress)
    {
        $this->nodes[$nodeName] = $nodeAddress;
    }
}
